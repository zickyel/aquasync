<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../dbcon.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'live_snapshot';

$response = [
    'success' => true,
    'db_connected' => $db_connected,
    'timestamp' => date('Y-m-d H:i:s'),
    'data' => []
];

// Fallback / default data if database is not reachable
$default_settings = [
    'setting_id' => 1,
    'min_threshold' => 60,
    'max_threshold' => 95,
    'auto_refill' => 1,
    'manual_pump' => 0
];

$default_user = [
    'user_id' => 1,
    'role' => 'master',
    'first_name' => 'Ezekiel',
    'last_name' => 'Reyes',
    'email' => 'ezekielreyes@gmail.com',
    'username' => 'admin'
];

if (!$db_connected || !$conn) {
    // Provide realistic demo data so the app can be previewed immediately
    if ($type === 'live_snapshot') {
        $response['data'] = [
            'current_water_level' => 85,
            'current_liters' => 850,
            'max_capacity' => 1000,
            'pump_status' => 'OFF',
            'min_threshold' => 60,
            'max_threshold' => 95,
            'auto_mode' => true,
            'manual_pump' => 0,
            'cycles_today' => 8,
            'total_volume_today' => 3200,
            'recent_logs' => [
                ['log_id' => 5, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-18 minutes'))],
                ['log_id' => 4, 'action' => 'ON',  'water_level' => 60, 'created_at' => date('Y-m-d H:i:s', strtotime('-26 minutes'))],
                ['log_id' => 3, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 15 mins'))],
                ['log_id' => 2, 'action' => 'ON',  'water_level' => 59, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 24 mins'))],
                ['log_id' => 1, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours 40 mins'))]
            ],
            'chart_history' => [
                ['time' => '08:00', 'level' => 95],
                ['time' => '08:30', 'level' => 84],
                ['time' => '09:00', 'level' => 71],
                ['time' => '09:30', 'level' => 60],
                ['time' => '09:40', 'level' => 95],
                ['time' => '10:00', 'level' => 88],
                ['time' => '10:15', 'level' => 85]
            ]
        ];
    } elseif ($type === 'pump_logs') {
        $response['data'] = [
            'logs' => [
                ['log_id' => 6, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-18 mins'))],
                ['log_id' => 5, 'action' => 'ON',  'water_level' => 60, 'created_at' => date('Y-m-d H:i:s', strtotime('-26 mins'))],
                ['log_id' => 4, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 15 mins'))],
                ['log_id' => 3, 'action' => 'ON',  'water_level' => 59, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour 24 mins'))],
                ['log_id' => 2, 'action' => 'OFF', 'water_level' => 95, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours 40 mins'))],
                ['log_id' => 1, 'action' => 'ON',  'water_level' => 60, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours 52 mins'))],
            ],
            'min_threshold' => 60,
            'max_threshold' => 95,
            'pump_status'   => 'OFF',
            'current_level' => 85,
        ];
    } elseif ($type === 'settings') {
        $response['data'] = $default_settings;
    } elseif ($type === 'users') {
        $response['data'] = [$default_user];
    }
    echo json_encode($response);
    exit;
}

// When Database is Connected
if ($type === 'live_snapshot') {
    // 1. Get fallback settings
    $min_threshold = 25;
    $max_threshold = 85;
    $set_res = $conn->query("SELECT min_threshold, max_threshold FROM settings ORDER BY setting_id DESC LIMIT 1");
    if ($set_res && $set_row = $set_res->fetch_assoc()) {
        $min_threshold = (int)$set_row['min_threshold'];
        $max_threshold = (int)$set_row['max_threshold'];
    }

    // 2. Base metrics strictly on the LATEST ROW INSERTED in water_levels table
    $current_water_level = 0;
    $pump_status = 'OFF';
    $latest_recorded_at = null;
    $latest_description = '';

    $lvl_res = $conn->query("SELECT level_id, water_level, action, min_threshold, max_threshold, description, recorded_at FROM water_levels ORDER BY level_id DESC LIMIT 1");
    if ($lvl_res && $lvl_row = $lvl_res->fetch_assoc()) {
        $current_water_level = (int)$lvl_row['water_level'];
        
        // Exact pump status as recorded by IoT device in database
        if (!empty($lvl_row['action']) && in_array(strtoupper(trim($lvl_row['action'])), ['ON', 'OFF'])) {
            $pump_status = strtoupper(trim($lvl_row['action']));
        } else {
            $pump_status = ($current_water_level <= $min_threshold) ? 'ON' : 'OFF';
        }

        // Active thresholds followed at the time of insertion
        if (!empty($lvl_row['min_threshold'])) $min_threshold = (int)$lvl_row['min_threshold'];
        if (!empty($lvl_row['max_threshold'])) $max_threshold = (int)$lvl_row['max_threshold'];
        $latest_recorded_at = $lvl_row['recorded_at'];
        $latest_description = $lvl_row['description'];
    }

    // 3. Count Refill Cycles today based on water_levels database entries
    $cycles_today = 0;
    $today_res = $conn->query("SELECT COUNT(*) AS total FROM water_levels WHERE (description = 'Water level dropped' OR (action = 'ON' AND description LIKE '%dropped%')) AND DATE(recorded_at) = CURDATE()");
    if ($today_res && $today_row = $today_res->fetch_assoc()) {
        $cycles_today = (int)$today_row['total'];
    }
    // Fallback: If no distinct dropped events found yet, count distinct ON time clusters today
    if ($cycles_today === 0) {
        $fallback_c = $conn->query("SELECT COUNT(DISTINCT DATE_FORMAT(recorded_at, '%Y-%m-%d %H:%i')) AS total FROM water_levels WHERE action = 'ON' AND DATE(recorded_at) = CURDATE()");
        if ($fallback_c && $fc_row = $fallback_c->fetch_assoc()) {
            $cycles_today = (int)$fc_row['total'];
        }
    }

    // 4. Recent pump logs from MySQL pump_logs VIEW (LIMIT 5)
    $recent_logs = [];
    $logs_query = $conn->query("SELECT * FROM pump_logs ORDER BY log_id DESC LIMIT 5");
    if ($logs_query) {
        while ($row = $logs_query->fetch_assoc()) {
            $recent_logs[] = $row;
        }
    }

    // 5. Chart history (last 50 water levels, oldest→newest para sa chart)
    $chart_history = [];
    $hist_query = $conn->query("SELECT water_level, recorded_at FROM water_levels ORDER BY level_id DESC LIMIT 50");
    if ($hist_query) {
        $temp_hist = [];
        while ($row = $hist_query->fetch_assoc()) {
            $temp_hist[] = [
                'time'  => date('H:i', strtotime($row['recorded_at'])),
                'level' => (int)$row['water_level']
            ];
        }
        $chart_history = array_reverse($temp_hist);
    }

    $response['data'] = [
        'current_water_level' => $current_water_level,
        'current_liters'      => round(($current_water_level / 100) * 1000),
        'max_capacity'        => 1000,
        'pump_status'         => $pump_status,
        'min_threshold'       => $min_threshold,
        'max_threshold'       => $max_threshold,
        'latest_recorded_at'  => $latest_recorded_at,
        'latest_description'  => $latest_description,
        'auto_mode'           => true,
        'cycles_today'        => $cycles_today,
        'total_volume_today'  => $cycles_today * 350,
        'recent_logs'         => $recent_logs,
        'chart_history'       => $chart_history
    ];
} elseif ($type === 'pump_logs') {
    // Get current thresholds from settings
    $min_thr = 60;
    $max_thr = 95;
    $set_res2 = $conn->query("SELECT min_threshold, max_threshold FROM settings ORDER BY setting_id DESC LIMIT 1");
    if ($set_res2 && $set_row2 = $set_res2->fetch_assoc()) {
        $min_thr = (int)$set_row2['min_threshold'];
        $max_thr = (int)$set_row2['max_threshold'];
    }

    // Get latest pump action to know if pump is currently ON
    $current_pump_status = 'OFF';
    $cur_pump_res = $conn->query("SELECT action FROM pump_logs ORDER BY log_id DESC LIMIT 1");
    if ($cur_pump_res && $cur_pump_row = $cur_pump_res->fetch_assoc()) {
        $current_pump_status = $cur_pump_row['action'];
    }

    // Get current water level
    $current_lvl = 0;
    $cur_lvl_res = $conn->query("SELECT water_level FROM water_levels ORDER BY level_id DESC LIMIT 1");
    if ($cur_lvl_res && $cur_lvl_row = $cur_lvl_res->fetch_assoc()) {
        $current_lvl = (int)$cur_lvl_row['water_level'];
    }

    $action_filter = isset($_GET['action']) ? $_GET['action'] : '';
    $sql = "SELECT * FROM pump_logs";
    if ($action_filter === 'ON' || $action_filter === 'OFF') {
        $sql .= " WHERE action = '" . $conn->real_escape_string($action_filter) . "'";
    }
    $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 50;
    $sql .= " ORDER BY log_id DESC LIMIT " . $limit;
    
    $logs = [];
    $res = $conn->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $logs[] = $r;
        }
    }
    $response['data'] = [
        'logs'           => $logs,
        'min_threshold'  => $min_thr,
        'max_threshold'  => $max_thr,
        'pump_status'    => $current_pump_status,
        'current_level'  => $current_lvl,
    ];
} elseif ($type === 'settings') {
    $set_res = $conn->query("
        SELECT s.*, u.first_name, u.last_name, u.username, u.role
        FROM settings s
        LEFT JOIN users u ON s.updated_by = u.user_id
        ORDER BY s.setting_id DESC
        LIMIT 1
    ");
    if ($set_res && $row = $set_res->fetch_assoc()) {
        $full_name = trim(($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
        $response['data'] = [
            'setting_id'          => (int)$row['setting_id'],
            'min_threshold'       => (int)$row['min_threshold'],
            'max_threshold'       => (int)$row['max_threshold'],
            'manual_pump'         => isset($row['manual_pump']) ? (int)$row['manual_pump'] : 0,
            'updated_by'          => isset($row['updated_by']) ? (int)$row['updated_by'] : null,
            'updated_by_name'     => $full_name !== '' ? $full_name : null,
            'updated_by_username' => $row['username'] ?? null,
            'updated_by_role'     => isset($row['role']) ? strtolower(trim($row['role'])) : null,
            'updated_at'          => $row['updated_at'] ?? null
        ];
    } else {
        $response['data'] = $default_settings;
    }
} elseif ($type === 'settings_history') {
    $history = [];
    $hist_res = $conn->query("
        SELECT
            h.history_id,
            h.setting_id,
            h.old_min_threshold,
            h.old_max_threshold,
            h.new_min_threshold,
            h.new_max_threshold,
            h.changed_by,
            h.changed_at,
            u.first_name,
            u.last_name,
            u.username,
            u.role
        FROM settings_history h
        LEFT JOIN users u ON h.changed_by = u.user_id
        ORDER BY h.changed_at DESC, h.history_id DESC
        LIMIT 50
    ");
    if ($hist_res && $hist_res->num_rows > 0) {
        while ($h = $hist_res->fetch_assoc()) {
            $full_name = trim(($h['first_name'] ?? '') . ' ' . ($h['last_name'] ?? ''));
            $history[] = [
                'history_id'          => (int)$h['history_id'],
                'setting_id'          => (int)$h['setting_id'],
                'old_min_threshold'   => (int)$h['old_min_threshold'],
                'old_max_threshold'   => (int)$h['old_max_threshold'],
                'new_min_threshold'   => (int)$h['new_min_threshold'],
                'new_max_threshold'   => (int)$h['new_max_threshold'],
                'changed_by'          => isset($h['changed_by']) ? (int)$h['changed_by'] : null,
                'changed_by_name'     => $full_name !== '' ? $full_name : null,
                'changed_by_username' => $h['username'] ?? null,
                'changed_by_role'     => isset($h['role']) ? strtolower(trim($h['role'])) : null,
                'changed_at'          => $h['changed_at']
            ];
        }
    }
    $response['data'] = $history;
} elseif ($type === 'users') {
    $users = [];
    $res = $conn->query("SELECT user_id, role, first_name, last_name, email, username, created_at FROM users ORDER BY user_id ASC");
    if ($res && $res->num_rows > 0) {
        while ($u = $res->fetch_assoc()) {
            $u['role'] = strtolower(trim($u['role'] ?? 'admin'));
            $users[] = $u;
        }
    } else {
        $users[] = $default_user;
    }
    $response['data'] = $users;
} elseif ($type === 'water_levels') {
    // Ibabalik ang water_levels records mula latest hanggang oldest
    $limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;
    $records = [];
    $res = $conn->query("SELECT level_id, water_level, min_threshold, max_threshold, action, description, recorded_at
                         FROM water_levels
                         ORDER BY level_id DESC
                         LIMIT $limit");
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $records[] = [
                'level_id'      => (int)$r['level_id'],
                'water_level'   => (int)$r['water_level'],
                'min_threshold' => (int)$r['min_threshold'],
                'max_threshold' => (int)$r['max_threshold'],
                'action'        => $r['action'] ?? '',
                'description'   => $r['description'] ?? '',
                'recorded_at'   => $r['recorded_at']
            ];
        }
    }
    $response['data'] = $records;
}

echo json_encode($response);
?>
