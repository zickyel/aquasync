<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, ngrok-skip-browser-warning');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/../dbcon.php';

// Accept parameters from POST, GET, or raw JSON
$raw_input = file_get_contents('php://input');
$json_data = json_decode($raw_input, true);

$input = $_POST;
if (!empty($_GET)) {
    $input = array_merge($input, $_GET);
}
if (is_array($json_data)) {
    $input = array_merge($input, $json_data);
}

$action = isset($input['action']) ? trim($input['action']) : '';

$response = [
    'success' => false,
    'message' => 'No action or data specified',
    'db_connected' => $db_connected
];

if (!$db_connected || !$conn) {
    $response['success'] = false;
    $response['message'] = 'Database connection error: cannot connect to MySQL.';
    echo json_encode($response);
    exit;
}

// Helper function to insert water level reading and handle pump logs
function handleWaterLevelInsert($conn, $input) {
    $water_level = null;
    if (isset($input['water_level']) && $input['water_level'] !== '') {
        $water_level = (int)$input['water_level'];
    } elseif (isset($input['sensor_value']) && $input['sensor_value'] !== '') {
        $raw_adc = (int)$input['sensor_value'];
        // Calibration mapping: 90 dry -> 1050 wet (ESP32-S3)
        $water_level = (int)round((max(0, $raw_adc - 90) / (1050.0 - 90)) * 100);
    } elseif (isset($input['level']) && $input['level'] !== '') {
        $water_level = (int)$input['level'];
    }

    if ($water_level === null) {
        return [
            'success' => false,
            'message' => 'Missing parameter: water_level (0-100) or sensor_value.'
        ];
    }

    $water_level = max(0, min(100, $water_level));

    // Get current thresholds and pump mode from settings
    $min_threshold = 25;
    $max_threshold = 85;
    $manual_pump   = 0;

    $set_q = $conn->query("SELECT min_threshold, max_threshold, manual_pump FROM settings ORDER BY setting_id DESC LIMIT 1");
    if ($set_q && $s_row = $set_q->fetch_assoc()) {
        $min_threshold = (int)($s_row['min_threshold'] ?? 25);
        $max_threshold = (int)($s_row['max_threshold'] ?? 85);
        $manual_pump   = (int)($s_row['manual_pump'] ?? 0);
    }

    if (isset($input['min_threshold']) && is_numeric($input['min_threshold'])) {
        $min_threshold = (int)$input['min_threshold'];
    }
    if (isset($input['max_threshold']) && is_numeric($input['max_threshold'])) {
        $max_threshold = (int)$input['max_threshold'];
    }

    // 1. Insert into water_levels table
    $stmt = $conn->prepare("INSERT INTO water_levels (water_level, min_threshold, max_threshold, recorded_at) VALUES (?, ?, ?, NOW())");
    $inserted = false;
    if ($stmt) {
        $stmt->bind_param("iii", $water_level, $min_threshold, $max_threshold);
        $inserted = $stmt->execute();
        $stmt->close();
    }

    if (!$inserted) {
        return [
            'success' => false,
            'message' => 'Failed to insert into water_levels table: ' . $conn->error
        ];
    }

    // 2. Determine pump action and log into pump_logs table
    $last_pump = 'OFF';
    $log_q = $conn->query("SELECT action FROM pump_logs ORDER BY log_id DESC LIMIT 1");
    if ($log_q && $l_row = $log_q->fetch_assoc()) {
        $last_pump = $l_row['action'];
    }

    $new_pump_action = $last_pump;
    $log_desc = '';

    if ($manual_pump === 1) {
        if ($water_level >= 100) {
            $manual_pump = 0;
            $conn->query("UPDATE settings SET manual_pump = 0 WHERE setting_id = 1");
            $new_pump_action = 'OFF';
            $log_desc = 'Manual Pump Stopped (Tank 100% Full)';
        } else {
            $new_pump_action = 'ON';
            $log_desc = 'Manual Pump Active (via Web App)';
        }
    } else {
        if ($water_level <= $min_threshold) {
            $new_pump_action = 'ON';
            $log_desc = "Auto Refill: Level ($water_level%) <= Min ($min_threshold%)";
        } elseif ($water_level >= $max_threshold) {
            $new_pump_action = 'OFF';
            $log_desc = "Auto Cutoff: Level ($water_level%) >= Max ($max_threshold%)";
        }
    }

    // If pump status changed, record it in pump_logs
    $pump_logged = false;
    if ($new_pump_action !== $last_pump) {
        $p_stmt = $conn->prepare("INSERT INTO pump_logs (action, water_level, description, created_at) VALUES (?, ?, ?, NOW())");
        if ($p_stmt) {
            $log_desc = substr($log_desc, 0, 100);
            $p_stmt->bind_param("sis", $new_pump_action, $water_level, $log_desc);
            $pump_logged = $p_stmt->execute();
            $p_stmt->close();
        }
    }

    return [
        'success'         => true,
        'message'         => "Successfully inserted water level ($water_level%) into database.",
        'water_level'     => $water_level,
        'pump_status'     => $new_pump_action,
        'pump_logged'     => $pump_logged,
        'min_threshold'   => $min_threshold,
        'max_threshold'   => $max_threshold,
        'manual_pump'     => $manual_pump,
        'recorded_at'     => date('Y-m-d H:i:s')
    ];
}

// Automatic detection: If water_level or sensor_value is passed directly without an action,
// or if action is insert/telemetry/simulate
$telemetry_actions = ['', 'insert', 'insert_water_level', 'insert_telemetry', 'record_reading', 'simulate_reading'];
if (in_array(strtolower($action), $telemetry_actions) && (isset($input['water_level']) || isset($input['sensor_value']) || isset($input['level']))) {
    $res = handleWaterLevelInsert($conn, $input);
    echo json_encode($res);
    exit;
}

switch ($action) {
    case 'insert_water_level':
    case 'insert_telemetry':
    case 'record_reading':
    case 'simulate_reading':
        $res = handleWaterLevelInsert($conn, $input);
        echo json_encode($res);
        exit;

    case 'manual_pump_toggle':
    case 'toggle_pump':
        $pump_action = isset($input['pump_action']) ? strtoupper(trim($input['pump_action'])) : 'OFF';
        if (!in_array($pump_action, ['ON', 'OFF'])) {
            $response['message'] = 'Invalid pump action. Must be ON or OFF.';
            break;
        }

        // Get latest water level
        $current_level = 50;
        $lvl_q = $conn->query("SELECT water_level FROM water_levels ORDER BY level_id DESC LIMIT 1");
        if ($lvl_q && $l_r = $lvl_q->fetch_assoc()) {
            $current_level = (int)$l_r['water_level'];
        }

        $manual_cmd = ($pump_action === 'ON') ? 1 : 0;

        // 1. Update manual_pump flag in settings table
        $s_chk = $conn->query("SELECT setting_id FROM settings LIMIT 1");
        if ($s_chk && $s_chk->num_rows > 0) {
            $s_row = $s_chk->fetch_assoc();
            $u_stmt = $conn->prepare("UPDATE settings SET manual_pump = ? WHERE setting_id = ?");
            $u_stmt->bind_param("ii", $manual_cmd, $s_row['setting_id']);
            $u_stmt->execute();
            $u_stmt->close();
        }

        // 2. Insert into pump_logs
        $desc = ($pump_action === 'ON') ? 'Manual Pump Override: Turned ON via Web App' : 'Manual Pump Override: Switched to AUTO/OFF via Web App';
        $desc = substr($desc, 0, 100);

        $stmt = $conn->prepare("INSERT INTO pump_logs (action, water_level, description, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sis", $pump_action, $current_level, $desc);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Pump manually switched " . $pump_action . " at {$current_level}% water level.";
                $response['pump_status'] = $pump_action;
                $response['manual_pump'] = $manual_cmd;
            } else {
                $response['message'] = 'Failed to record pump action: ' . $conn->error;
            }
            $stmt->close();
        }
        break;

    case 'insert_pump_log':
    case 'log_pump':
        $pump_action = isset($input['pump_action']) ? strtoupper(trim($input['pump_action'])) : 'OFF';
        if (!in_array($pump_action, ['ON', 'OFF'])) {
            $pump_action = 'OFF';
        }
        $p_lvl = isset($input['water_level']) ? (int)$input['water_level'] : 50;
        $p_desc = isset($input['description']) ? trim($input['description']) : "Pump turned $pump_action";
        $p_desc = substr($p_desc, 0, 100);

        $stmt = $conn->prepare("INSERT INTO pump_logs (action, water_level, description, created_at) VALUES (?, ?, ?, NOW())");
        if ($stmt) {
            $stmt->bind_param("sis", $pump_action, $p_lvl, $p_desc);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = "Successfully inserted pump log: $pump_action at $p_lvl%";
            } else {
                $response['message'] = 'Database error: ' . $conn->error;
            }
            $stmt->close();
        }
        break;

    case 'update_thresholds':
        $min = isset($input['min_threshold']) ? (int)$input['min_threshold'] : 25;
        $max = isset($input['max_threshold']) ? (int)$input['max_threshold'] : 85;

        $min = max(5, min(95, $min));
        $max = max(10, min(100, $max));

        if ($min >= $max) {
            $response['message'] = 'Minimum threshold must be lower than maximum threshold.';
            break;
        }

        // Check if settings record exists
        $check = $conn->query("SELECT setting_id FROM settings LIMIT 1");
        if ($check && $check->num_rows > 0) {
            $row = $check->fetch_assoc();
            $stmt = $conn->prepare("UPDATE settings SET min_threshold = ?, max_threshold = ? WHERE setting_id = ?");
            $stmt->bind_param("iii", $min, $max, $row['setting_id']);
        } else {
            $stmt = $conn->prepare("INSERT INTO settings (min_threshold, max_threshold) VALUES (?, ?)");
            $stmt->bind_param("ii", $min, $max);
        }

        if ($stmt && $stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Thresholds successfully updated: Min = {$min}%, Max = {$max}%";
            $response['data'] = ['min_threshold' => $min, 'max_threshold' => $max];
        } else {
            $response['message'] = 'Database update error: ' . $conn->error;
        }
        break;

    case 'add_user':
        $actor_id = (int)($_SESSION['user_id'] ?? 0);
        $actor_role = strtolower(trim($_SESSION['role'] ?? ''));
        if ($actor_id > 0) {
            $role_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
            if ($role_stmt) {
                $role_stmt->bind_param("i", $actor_id);
                $role_stmt->execute();
                $role_res = $role_stmt->get_result();
                if ($role_res && $role_row = $role_res->fetch_assoc()) {
                    $actor_role = strtolower(trim($role_row['role']));
                }
                $role_stmt->close();
            }
        }
        if ($actor_role !== 'master') {
            $response['message'] = 'Only Master Administrators can add new administrator accounts.';
            break;
        }

        $first_name = trim($input['first_name'] ?? '');
        $last_name  = trim($input['last_name'] ?? '');
        $email      = trim($input['email'] ?? '');
        $username   = trim($input['username'] ?? '');
        $password   = trim($input['password'] ?? '');

        if (!$username || !$first_name || !$email || !$password) {
            $response['message'] = 'First name, email, username, and password are required.';
            break;
        }

        // Hash password securely before inserting into database
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("INSERT INTO users (role, first_name, last_name, email, username, password, created_at) VALUES ('admin', ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("sssss", $first_name, $last_name, $email, $username, $hashed_password);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Admin user @{$username} created successfully.";
        } else {
            $response['message'] = "Error creating user: " . $conn->error;
        }
        break;

    case 'update_user':
        $user_id    = (int)($input['user_id'] ?? 1);
        $first_name = trim($input['first_name'] ?? '');
        $last_name  = trim($input['last_name'] ?? '');
        $email      = trim($input['email'] ?? '');
        $username   = trim($input['username'] ?? '');
        $password   = trim($input['password'] ?? '');

        if ($password !== '') {
            // Hash updated password securely before storing in database
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, username=?, password=? WHERE user_id=?");
            $stmt->bind_param("sssssi", $first_name, $last_name, $email, $username, $hashed_password, $user_id);
        } else {
            $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, username=? WHERE user_id=?");
            $stmt->bind_param("ssssi", $first_name, $last_name, $email, $username, $user_id);
        }

        if ($stmt && $stmt->execute()) {
            $response['success'] = true;
            $response['message'] = "Profile details updated successfully!";
        } else {
            $response['message'] = "Error updating user: " . $conn->error;
        }
        break;

    case 'delete_user':
        $target_id = (int)($input['user_id'] ?? 0);
        $actor_id = (int)($_SESSION['user_id'] ?? 0);
        $actor_role = strtolower(trim($_SESSION['role'] ?? ''));

        if ($actor_id <= 0 || $target_id <= 0) {
            $response['message'] = 'Invalid user request.';
            break;
        }

        // Verify actor role from database
        $actor_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
        if ($actor_stmt) {
            $actor_stmt->bind_param("i", $actor_id);
            $actor_stmt->execute();
            $actor_res = $actor_stmt->get_result();
            if ($actor_res && $actor_row = $actor_res->fetch_assoc()) {
                $actor_role = strtolower(trim($actor_row['role']));
            }
            $actor_stmt->close();
        }

        // Verify target user exists and get target role
        $target_role = '';
        $target_stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ? LIMIT 1");
        if ($target_stmt) {
            $target_stmt->bind_param("i", $target_id);
            $target_stmt->execute();
            $target_res = $target_stmt->get_result();
            if ($target_res && $target_row = $target_res->fetch_assoc()) {
                $target_role = strtolower(trim($target_row['role']));
            }
            $target_stmt->close();
        }

        if ($target_role === '') {
            $response['message'] = 'User account not found.';
            break;
        }

        if ($target_role === 'master') {
            $response['message'] = 'Master Administrator account cannot be deleted.';
            break;
        }

        $is_self = ($target_id === $actor_id);
        $can_delete = ($actor_role === 'master') || ($actor_role === 'admin' && $is_self);

        if (!$can_delete) {
            $response['message'] = 'You do not have permission to delete this account.';
            break;
        }

        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $target_id);
        if ($stmt->execute()) {
            $response['success'] = true;
            $response['self_deleted'] = $is_self;
            $response['message'] = $is_self
                ? 'Your account has been deleted. Logging out...'
                : 'Administrator account removed.';
            if ($is_self) {
                $_SESSION = [];
                session_destroy();
            }
        } else {
            $response['message'] = "Error removing user.";
        }
        break;

    default:
        $response['message'] = "Invalid action: '$action'";
        break;
}

echo json_encode($response);
?>
