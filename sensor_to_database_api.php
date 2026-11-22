<?php
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, ngrok-skip-browser-warning');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

include 'dbcon.php';

if (!$db_connected || !$conn) {
    echo json_encode([
        "status"  => "error",
        "message" => "Database connection error"
    ]);
    exit;
}

$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// Support both JSON payload and $_POST/$_REQUEST
if (!is_array($data)) {
    $data = $_REQUEST;
}

if (is_array($data) && (isset($data['water_level']) || isset($data['sensor_value']))) {
    $water_level = isset($data['water_level']) ? intval($data['water_level']) : 0;

    // Support raw ADC if sensor_value is passed
    if (!isset($data['water_level']) && isset($data['sensor_value'])) {
        $adc = intval($data['sensor_value']);
        $water_level = (int)round((max(0, $adc - 90) / (1050.0 - 90)) * 100);
    }
    $water_level = max(0, min(100, $water_level));

    // Thresholds: currently followed by ESP32 (from payload)
    $min_threshold = isset($data['min_threshold']) ? intval($data['min_threshold']) : null;
    $max_threshold = isset($data['max_threshold']) ? intval($data['max_threshold']) : null;

    // Also fetch master thresholds from settings table
    $settings_min = null;
    $settings_max = null;
    $s_res = $conn->query("SELECT min_threshold, max_threshold FROM settings ORDER BY setting_id DESC LIMIT 1");
    if ($s_res && $s_row = $s_res->fetch_assoc()) {
        $settings_min = intval($s_row['min_threshold']);
        $settings_max = intval($s_row['max_threshold']);
    }

    if ($min_threshold === null) $min_threshold = ($settings_min !== null) ? $settings_min : 25;
    if ($max_threshold === null) $max_threshold = ($settings_max !== null) ? $settings_max : 85;

    // Action: 'ON' o 'OFF'
    $action = '';
    if (isset($data['action']) && in_array(strtoupper(trim($data['action'])), ['ON', 'OFF'])) {
        $action = strtoupper(trim($data['action']));
    } else {
        $action = ($water_level <= $min_threshold) ? 'ON' : 'OFF';
    }

    // Description: short concise format matching table events
    $description = '';
    if (isset($data['description']) && trim($data['description']) !== '') {
        $description = trim($data['description']);
    } else {
        if ($water_level >= $max_threshold) {
            $description = "Target level achieved";
        } elseif ($water_level <= $min_threshold) {
            $description = "Water level dropped";
        } elseif ($action === 'ON') {
            $description = "Water level raised";
        } else {
            $description = "Normal water level";
        }
    }
    // Limit to 100 characters to fit varchar(100) column
    $description = substr($description, 0, 100);

    // Prepare and insert query into water_levels table
    $stmt = $conn->prepare("INSERT INTO water_levels (water_level, min_threshold, max_threshold, action, description, recorded_at) VALUES (?, ?, ?, ?, ?, NOW())");

    if ($stmt) {
        $stmt->bind_param("iiiss", $water_level, $min_threshold, $max_threshold, $action, $description);

        if ($stmt->execute()) {
            echo json_encode([
                "status"        => "success",
                "message"       => "Data saved successfully into water_levels table",
                "water_level"   => $water_level,
                "saved_min"     => $min_threshold,
                "saved_max"     => $max_threshold,
                "action"        => $action,
                "description"   => $description,
                "min_threshold" => ($settings_min !== null) ? $settings_min : $min_threshold,
                "max_threshold" => ($settings_max !== null) ? $settings_max : $max_threshold,
                "recorded_at"   => date('Y-m-d H:i:s')
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Execute failed: " . $stmt->error]);
        }
        $stmt->close();
    } else {
        echo json_encode(["status" => "error", "message" => "Prepare failed: " . $conn->error]);
    }
} else {
    echo json_encode([
        "status"        => "error",
        "message"       => "Invalid JSON payload: water_level is required",
        "received_data" => $data
    ]);
}

$conn->close();
?>
