<?php
session_start();
require_once __DIR__ . '/dbcon.php';

// Route Guard: Require Authentication
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Determine initial active page from GET parameter, default to 'dashboard'
$page = isset($_GET['page']) ? strtolower(trim($_GET['page'])) : 'dashboard';
if (!in_array($page, ['dashboard', 'pumplogs', 'reports', 'settings', 'account'])) {
    $page = 'dashboard';
}

// User credentials from session
$admin_name = $_SESSION['name'] ?? 'Ezekiel Reyes';
$admin_email = $_SESSION['email'] ?? 'ezekielreyes@gmail.com';
$admin_username = $_SESSION['username'] ?? 'admin';

// Default / DB Telemetry values
$current_water_level = 85;
$min_threshold = 60;
$max_threshold = 90;
$pump_status = 'OFF';

if ($db_connected && $conn) {
    // 1. Settings
    $set_q = $conn->query("SELECT min_threshold, max_threshold FROM settings ORDER BY setting_id DESC LIMIT 1");
    if ($set_q && $s_r = $set_q->fetch_assoc()) {
        $min_threshold = (int)$s_r['min_threshold'];
        $max_threshold = (int)$s_r['max_threshold'];
    }

    // 2. Base dashboard strictly on the LAST ROW INSERTED into water_levels table
    $current_water_level = 0;
    $pump_status = 'OFF';
    $lvl_q = $conn->query("SELECT water_level, action, min_threshold, max_threshold FROM water_levels ORDER BY level_id DESC LIMIT 1");
    if ($lvl_q && $l_r = $lvl_q->fetch_assoc()) {
        $current_water_level = (int)$l_r['water_level'];
        if (!empty($l_r['action'])) {
            $pump_status = strtoupper(trim($l_r['action']));
        }
        if (!empty($l_r['min_threshold'])) $min_threshold = (int)$l_r['min_threshold'];
        if (!empty($l_r['max_threshold'])) $max_threshold = (int)$l_r['max_threshold'];
    }

    // 3. Count Refill Cycles today
    $cycles_today = 0;
    $cyc_q = $conn->query("SELECT COUNT(*) AS total FROM water_levels WHERE (description = 'Water level dropped' OR (action = 'ON' AND description LIKE '%dropped%')) AND DATE(recorded_at) = CURDATE()");
    if ($cyc_q && $cyc_r = $cyc_q->fetch_assoc()) {
        $cycles_today = (int)$cyc_r['total'];
    }

    // 4. Initial 5 recent pump operations from pump_logs VIEW
    $dashboard_logs = [];
    $d_logs_q = $conn->query("SELECT * FROM pump_logs ORDER BY log_id DESC LIMIT 5");
    if ($d_logs_q) {
        while ($dl_row = $d_logs_q->fetch_assoc()) {
            $dashboard_logs[] = $dl_row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AquaSync - Smart Auto Refilling System Panel</title>
    <!-- Favicon / Water drop icon -->
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%230284c7'><path d='M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z'/></svg>">
    
    <!-- Bootstrap 5 CSS (local) -->
    <link rel="stylesheet" href="bootstrap5/css/bootstrap.min.css">
    
    <!-- External Modular Stylesheet (Light Blue & White Theme) -->
    <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
    
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    <!-- SweetAlert2 (local) -->
    <script src="bootstrap5/js/sweetalert2.all.min.js"></script>
</head>
<body>

    <!-- BACKDROP OVERLAY FOR MOBILE SIDEBAR -->
    <div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleMobileSidebar()"></div>

    <!-- LEFT SIDEBAR SYSTEM NAVIGATION -->
    <aside class="sidebar" id="sidebarNav">
        <!-- BRAND LOGO & SYSTEM PANEL -->
        <div class="sidebar-brand-wrapper">
            <a href="?page=dashboard" class="brand-header" onclick="switchTab('dashboard', event)">
                <div class="brand-logo-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                    </svg>
                </div>
                <div class="brand-text-container">
                    <span class="brand-title">AquaSync</span>
                    <span class="brand-subtext"><span class="dot"></span> System Panel</span>
                </div>
            </a>
            <!-- Mobile Close Button -->
            <button class="sidebar-close-btn" onclick="toggleMobileSidebar()" aria-label="Close sidebar">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>
        </div>

        <!-- NAVIGATION LINKS -->
        <div class="sidebar-nav-container">
            <div class="nav-category-label">Water Station Control</div>
            <ul class="nav-list">
                <!-- 1. Dashboard → Real-time monitoring -->
                <li>
                    <a href="?page=dashboard" class="nav-item-btn <?= ($page === 'dashboard') ? 'active' : '' ?>" id="nav-dashboard" onclick="switchTab('dashboard', event)">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="9"></rect>
                            <rect x="14" y="3" width="7" height="5"></rect>
                            <rect x="14" y="12" width="7" height="9"></rect>
                            <rect x="3" y="16" width="7" height="5"></rect>
                        </svg>
                        <div class="nav-text-col">
                            <span class="nav-main-text">Dashboard</span>
                        </div>
                    </a>
                </li>

                <!-- 2. Pump Logs → Refill history -->
                <li>
                    <a href="?page=pumplogs" class="nav-item-btn <?= ($page === 'pumplogs') ? 'active' : '' ?>" id="nav-pumplogs" onclick="switchTab('pumplogs', event)">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/>
                            <circle cx="12" cy="14" r="3"></circle>
                        </svg>
                        <div class="nav-text-col">
                            <span class="nav-main-text">Pump Logs</span>
                        </div>
                    </a>
                </li>

                <!-- 3. Reports → Analytics -->
                <li>
                    <a href="?page=reports" class="nav-item-btn <?= ($page === 'reports') ? 'active' : '' ?>" id="nav-reports" onclick="switchTab('reports', event)">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="20" x2="18" y2="10"></line>
                            <line x1="12" y1="20" x2="12" y2="4"></line>
                            <line x1="6" y1="20" x2="6" y2="14"></line>
                        </svg>
                        <div class="nav-text-col">
                            <span class="nav-main-text">Reports</span>
                        </div>
                    </a>
                </li>

                <!-- 4. Settings → Threshold configuration -->
                <li>
                    <a href="?page=settings" class="nav-item-btn <?= ($page === 'settings') ? 'active' : '' ?>" id="nav-settings" onclick="switchTab('settings', event)">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="4" y1="21" x2="4" y2="14"></line>
                            <line x1="4" y1="10" x2="4" y2="3"></line>
                            <line x1="12" y1="21" x2="12" y2="12"></line>
                            <line x1="12" y1="8" x2="12" y2="3"></line>
                            <line x1="20" y1="21" x2="20" y2="16"></line>
                            <line x1="20" y1="12" x2="20" y2="3"></line>
                            <line x1="1" y1="14" x2="7" y2="14"></line>
                            <line x1="9" y1="8" x2="15" y2="8"></line>
                            <line x1="17" y1="16" x2="23" y2="16"></line>
                        </svg>
                        <div class="nav-text-col">
                            <span class="nav-main-text">Settings</span>
                        </div>
                    </a>
                </li>

                <!-- 5. Account → Admin management -->
                <li>
                    <a href="?page=account" class="nav-item-btn <?= ($page === 'account') ? 'active' : '' ?>" id="nav-account" onclick="switchTab('account', event)">
                        <svg class="nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        <div class="nav-text-col">
                            <span class="nav-main-text">Account</span>
                        </div>
                    </a>
                </li>

            </ul>
        </div>

        <!-- SIDEBAR BOTTOM: ESP32 HARDWARE CARD & ADMIN PROFILE -->
        <div class="sidebar-bottom">
            <div class="device-network-card">
                <div class="d-network-top">
                    <span class="d-network-label">ESP32 Hardware Node</span>
                    <span class="status-dot-green" title="ESP32 Online & Polling"></span>
                </div>
                <div class="d-network-title">ESP32 Controller</div>
                <div class="d-network-sub">Water Sensor + Pump Relay Active</div>
            </div>

            <!-- Admin Profile Strip -->
            <div class="user-profile-strip">
                <div class="user-info-left">
                    <div class="user-avatar-circle" id="sidebarAvatar">
                        <?= strtoupper(substr($admin_name, 0, 1)) ?>
                    </div>
                    <div class="user-text-details">
                        <span class="user-name-title" id="sidebarUserName"><?= $admin_name ?></span>
                        <span class="user-email-subtitle" id="sidebarUserEmail"><?= $admin_email ?></span>
                    </div>
                </div>
                <button class="user-quick-action-btn" title="Logout" onclick="handleLogout()">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                </button>
            </div>
        </div>
    </aside>

    <!-- MAIN BODY CONTENT WRAPPER -->
    <main class="main-wrapper">
        <!-- TOPBAR HEADER -->
        <header class="topbar-header">
            <div class="topbar-left">
                <!-- Mobile Hamburger Button -->
                <button class="btn-burger-menu" id="sidebarToggleBtn" aria-label="Toggle Navigation" onclick="toggleMobileSidebar()">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="3" y1="12" x2="21" y2="12"></line>
                        <line x1="3" y1="6" x2="21" y2="6"></line>
                        <line x1="3" y1="18" x2="21" y2="18"></line>
                    </svg>
                </button>

                <div class="topbar-page-title">
                    <div class="section-tag-badge topbar-section-badge" id="topbarBreadcrumb" style="color: #0284c7 !important; font-weight: 800 !important; font-size: 0.78rem !important; letter-spacing: 1.2px !important; text-transform: uppercase !important; font-family: 'Plus Jakarta Sans', sans-serif !important; display: inline-flex; align-items: center; gap: 7px;">
                        <span class="dot" style="width: 7px; height: 7px; border-radius: 50%; background-color: #0284c7 !important; display: inline-block; flex-shrink: 0;"></span>
                        <?php
                        $badgeMap = [
                            'dashboard' => 'REAL-TIME SENSOR &amp; PUMP MONITORING',
                            'pumplogs'  => 'AUDIT TRAIL &amp; LOG ARCHIVE',
                            'reports'   => 'METRICS &amp; PERFORMANCE',
                            'settings'  => 'THRESHOLD CONFIGURATION',
                            'account'   => 'USER ACCESS &amp; CREDENTIALS',
                        ];
                        echo $badgeMap[$page] ?? strtoupper($page);
                        ?>
                    </div>
                </div>
            </div>

            <div class="topbar-right-controls">
                <!-- Auto-Refill Status Pill -->
                <div class="live-status-badge d-none d-sm-flex">
                    <span class="status-dot-green"></span>
                    <span>Auto-Refill Active</span>
                </div>

                <!-- Live Date & Clock -->
                <div class="live-date-pill">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <span id="liveClockDisplay">Loading...</span>
                </div>

            </div>
        </header>

        <!-- CONTENT TABS -->
        <div class="content-area">

            <!-- ========================================== -->
            <!-- 1. DASHBOARD TAB (REAL-TIME MONITORING)    -->
            <!-- ========================================== -->
            <section id="tab-dashboard" class="tab-section <?= ($page === 'dashboard') ? 'active' : '' ?>">
                <!-- Title Header Row -->
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Water Station System Status</h1>
                        
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern-primary" onclick="switchTab('settings')">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="4" y1="21" x2="4" y2="14"></line>
                                <line x1="4" y1="10" x2="4" y2="3"></line>
                                <line x1="12" y1="21" x2="12" y2="12"></line>
                                <line x1="12" y1="8" x2="12" y2="3"></line>
                            </svg>
                            Threshold Configurations
                        </button>
                    </div>
                </div>

                <!-- Hero Banner Card -->
                <div class="hero-banner-card">
                    <div class="hero-content-col">
                        <h2 class="hero-title mb-0">Welcome back,<br> <span id="heroUserName">- <?= htmlspecialchars(explode(' ', $admin_name)[0]) ?></span></h2>
                    </div>
                    <div class="hero-badge-col position-relative" style="z-index: 2;">
                        <div class="hero-mini-tag mb-0">
                            <svg width="14" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                            </svg>
                            AUTOMATED REFILL CONTROLLER
                        </div>
                    </div>
                </div>

                <!-- 4 Key Metric Cards (Bootstrap Grid Responsive) -->
                <div class="snapshot-title-row">
                    <div>
                        <h3 class="snapshot-heading">Operational Metrics Snapshot</h3>
                        
                    </div>
                </div>

                <div class="row g-3 g-xl-4 mb-4">
                    <!-- Metric 1: Water Level -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-card-top">
                                <span class="stat-category-tag">Water Level</span>
                            </div>
                            <div class="stat-number-row mb-1">
                                <span class="stat-main-number" id="cardWaterLevelVal"><?= $current_water_level ?></span>
                                <span class="stat-unit">%</span>
                            </div>
                            <div class="progress my-2" style="height: 6px; border-radius: 4px; background: #e2e8f0;">
                                <div class="progress-bar bg-primary" id="cardWaterLevelBar" style="width: <?= $current_water_level ?>%; border-radius: 4px;"></div>
                            </div>
                            <div class="stat-card-footer">
                                <span class="stat-footer-text">Reservoir Status</span>
                            </div>
                        </div>
                    </div>

                    <!-- Metric 2: Pump Status -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-card-top">
                                <span class="stat-category-tag">Water Pump</span>
                            </div>
                            <div class="stat-number-row mb-1">
                                <span class="stat-main-number" id="cardPumpStatusText"><?= $pump_status ?></span>
                            </div>
                            <div class="stat-card-footer">
                                <span class="stat-footer-text">Relay State</span>
                            </div>
                        </div>
                    </div>

                    <!-- Metric 3: Refill Cycles Today -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-card-top">
                                <span class="stat-category-tag">Refill Cycles</span>
                            </div>
                            <div class="stat-number-row mb-1">
                                <span class="stat-main-number" id="cardCyclesCount"><?= $cycles_today ?></span>
                                <span class="stat-unit">today</span>
                            </div>
                            <div class="stat-card-footer">
                                <span class="stat-footer-text">Total Operations</span>
                            </div>
                        </div>
                    </div>

                    <!-- Metric 4: Threshold Config Window -->
                    <div class="col-12 col-sm-6 col-xl-3">
                        <div class="stat-card">
                            <div class="stat-card-top">
                                <span class="stat-category-tag">Safety Window</span>
                            </div>
                            <div class="stat-number-row mb-1">
                                <span class="stat-main-number"><span id="cardMinThresh"><?= $min_threshold ?></span> - <span id="cardMaxThresh"><?= $max_threshold ?></span></span>
                                <span class="stat-unit">%</span>
                            </div>
                            <div class="stat-card-footer">
                                <span class="stat-footer-text">Threshold Limits</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity Feed Table (Powered by pump_logs VIEW, Limit 10) -->
                <div class="white-panel">
                    <div class="panel-header-row">
                        <div>
                            <h4 class="panel-title">Recent Refill Operations</h4>
                            <p class="panel-subtitle">Latest activity pump logs (Powered by Esp32)</p>
                        </div>
                        <a href="?page=pumplogs" class="btn-modern-outline" onclick="switchTab('pumplogs', event)">
                            View All Pump Logs
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>Pump Action</th>
                                    <th>Water Level</th>
                                    <th>Event Description</th>
                                    <th>Recorded Time</th>
                                </tr>
                            </thead>
                            <tbody id="dashboardRecentLogsBody">
                                <?php if (empty($dashboard_logs)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">No pump operations recorded yet.</td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($dashboard_logs as $log): ?>
                                <tr class="log-row">
                                    <td><strong>#<?= htmlspecialchars($log['log_id']) ?></strong></td>
                                    <td>
                                        <?php if ($log['action'] === 'ON'): ?>
                                            <span class="badge-pump-on">ON</span>
                                        <?php else: ?>
                                            <span class="badge-pump-off">OFF</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($log['water_level']) ?>%</td>
                                    <td><?= htmlspecialchars($log['description']) ?></td>
                                    <td class="text-muted small"><?= htmlspecialchars($log['created_at']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </section>

            <!-- ========================================== -->
            <!-- 2. PUMP LOGS TAB (REFILL HISTORY)          -->
            <!-- ========================================== -->
            <section id="tab-pumplogs" class="tab-section <?= ($page === 'pumplogs') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Pump Logs & Refill History</h1>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern-primary" onclick="exportLogsToCSV()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export to CSV
                        </button>
                    </div>
                </div>

                <!-- Filter Controls -->
                <div class="white-panel mb-4">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-4">
                            <label class="form-label small fw-bold text-muted">Filter by Action</label>
                            <select class="form-select" id="logFilterAction" onchange="loadPumpLogsTable()">
                                <option value="">All Actions (ON & OFF)</option>
                                <option value="ON">Pump Started (ON)</option>
                                <option value="OFF">Pump Stopped (OFF)</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label small fw-bold text-muted">Search in Logs</label>
                            <input type="text" class="form-control" id="logSearchQuery" placeholder="Search by date, level, or ID..." onkeyup="filterLogRows()">
                        </div>
                        <div class="col-12 col-md-3 text-end d-flex align-items-end h-100">
                            <span class="badge bg-light text-primary border p-2 w-100 mt-md-4 text-center" id="logsCountBadge">
                                Showing latest records
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Logs Table Panel -->
                <div class="white-panel">
                    <div class="table-responsive">
                        <table class="modern-table" id="fullPumpLogsTable">
                            <thead>
                                <tr>
                                    <th>Log ID</th>
                                    <th>Pump Action</th>
                                    <th>Water Level</th>
                                    <th>Event Description</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="pumpLogsTableBody">
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Fetching pump logs...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 3. REPORTS TAB (ANALYTICS)                 -->
            <!-- ========================================== -->
            <section id="tab-reports" class="tab-section <?= ($page === 'reports') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Water Station Analytics & Reports</h1>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern-primary" onclick="exportWaterLevelsToCSV()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                <polyline points="7 10 12 15 17 10"></polyline>
                                <line x1="12" y1="15" x2="12" y2="3"></line>
                            </svg>
                            Export to CSV
                        </button>
                        <button class="btn-modern-outline" onclick="window.print()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="6 9 6 2 18 2 18 9"></polyline>
                                <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
                                <rect x="6" y="14" width="12" height="8"></rect>
                            </svg>
                            Print Summary Report
                        </button>
                    </div>
                </div>

                <!-- Charts Section -->
                <div class="row g-4 mb-4">
                    <!-- Left Chart: Water Level Trend Over Time -->
                    <div class="col-12 col-lg-8">
                        <div class="white-panel h-100">
                            <div class="panel-header-row">
                                <div>
                                    <h4 class="panel-title">Water Level Sensor History & Refill Curve</h4>
                                    <p class="panel-subtitle">Sensor readings recorded across refill cycles</p>
                                </div>
                            </div>
                            <div style="height: 320px; position: relative;">
                                <canvas id="waterTrendChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Right Chart: Refill Distribution -->
                    <div class="col-12 col-lg-4">
                        <div class="white-panel h-100">
                            <div class="panel-header-row">
                                <div>
                                    <h4 class="panel-title">Refills by Time of Day</h4>
                                    <p class="panel-subtitle">Hourly distribution of pump activations</p>
                                </div>
                            </div>
                            <div style="height: 320px; position: relative;">
                                <canvas id="refillDistributionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reports Table: Water Level Sensor History (Latest to Oldest) -->
                <div class="white-panel">
                    <div class="panel-header-row">
                        <div>
                            <h4 class="panel-title">Water Level Sensor Readings Archive</h4>
                            <p class="panel-subtitle">Historical records from sensor to database (Ordered: Latest to Oldest)</p>
                        </div>
                        <div>
                            <span class="badge bg-light text-primary border p-2" id="waterLevelsCountBadge">
                                Showing latest records
                            </span>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="modern-table" id="reportsWaterLevelsTable">
                            <thead>
                                <tr>
                                    <th>Level ID</th>
                                    <th>Water Level (%)</th>
                                    <th>Pump Action</th>
                                    <th>Threshold Limits</th>
                                    <th>Event Description</th>
                                    <th>Recorded Timestamp</th>
                                </tr>
                            </thead>
                            <tbody id="reportsWaterLevelsBody">
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">Loading water level records...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 4. SETTINGS TAB (THRESHOLD CONFIGURATION) -->
            <!-- ========================================== -->
            <section id="tab-settings" class="tab-section <?= ($page === 'settings') ? 'active' : '' ?>">
                <div class="section-header-row mb-4">
                    <div>
                        <h1 class="section-main-heading">Settings &amp; Threshold Configuration</h1>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button type="button" class="btn-modern-primary" onclick="saveThresholdSettings()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                <polyline points="17 21 17 13 7 13 7 21"></polyline>
                                <polyline points="7 3 7 8 15 8"></polyline>
                            </svg>
                            Save Configuration
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Sliders Form (Original Style - Full Width) -->
                    <div class="col-12">
                        <div class="white-panel">
                            <h4 class="panel-title mb-4">Auto Refill Thresholds</h4>

                            <!-- Minimum Threshold Slider -->
                            <div class="threshold-slider-group mb-4">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-heading">Minimum Threshold (Pump ON)</span>
                                    <span class="badge bg-primary-subtle text-primary fw-bold px-3 py-1 fs-6"><span id="minThreshDisplay"><?= $min_threshold ?></span>%</span>
                                </div>
                                <input type="range" class="range-slider-modern w-100" id="inputMinThreshold" min="10" max="60" step="1" value="<?= $min_threshold ?>" oninput="updateThresholdSliders(this.value, null)">
                                <!-- Tick marks: major every 10%, minor every 5% | Range: 10–60 = 50 units -->
                                <div class="slider-ticks" style="position:relative;width:100%;height:18px;margin-top:2px;">
                                    <?php
                                    // Min slider: 10 to 60, step 5
                                    $minS = 10; $maxS = 60; $range = $maxS - $minS;
                                    for ($v = $minS; $v <= $maxS; $v += 5):
                                        $pct = ($v - $minS) / $range * 100;
                                        $isMajor = ($v % 10 === 0);
                                        $h = $isMajor ? '10px' : '6px';
                                        $color = $isMajor ? '#94a3b8' : '#cbd5e1';
                                        $label = $isMajor ? "<span style='position:absolute;top:12px;left:50%;transform:translateX(-50%);font-size:0.7rem;color:#94a3b8;white-space:nowrap;'>{$v}%</span>" : '';
                                    ?>
                                    <span style="position:absolute;left:<?= $pct ?>%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;">
                                        <span style="display:block;width:1.5px;height:<?= $h ?>;background:<?= $color ?>;"></span>
                                        <?= $label ?>
                                    </span>
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <!-- Maximum Threshold Slider -->
                            <div class="threshold-slider-group">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <span class="fw-bold text-heading">Maximum Threshold (Pump OFF)</span>
                                    <span class="badge bg-success-subtle text-success fw-bold px-3 py-1 fs-6"><span id="maxThreshDisplay"><?= $max_threshold ?></span>%</span>
                                </div>
                                <input type="range" class="range-slider-modern w-100" id="inputMaxThreshold" min="80" max="90" step="1" value="<?= $max_threshold ?>" oninput="updateThresholdSliders(null, this.value)">
                                <!-- Tick marks: major every 10%, minor every 5% | Range: 80–90 = 10 units -->
                                <div class="slider-ticks" style="position:relative;width:100%;height:18px;margin-top:2px;">
                                    <?php
                                    // Max slider: 80 to 90, step 5
                                    $minS2 = 80; $maxS2 = 90; $range2 = $maxS2 - $minS2;
                                    for ($v2 = $minS2; $v2 <= $maxS2; $v2 += 5):
                                        $pct2 = ($v2 - $minS2) / $range2 * 100;
                                        $isMajor2 = ($v2 % 10 === 0);
                                        $h2 = $isMajor2 ? '10px' : '6px';
                                        $color2 = $isMajor2 ? '#94a3b8' : '#cbd5e1';
                                        $label2 = $isMajor2 ? "<span style='position:absolute;top:12px;left:50%;transform:translateX(-50%);font-size:0.7rem;color:#94a3b8;white-space:nowrap;'>{$v2}%</span>" : '';
                                    ?>
                                    <span style="position:absolute;left:<?= $pct2 ?>%;transform:translateX(-50%);display:flex;flex-direction:column;align-items:center;">
                                        <span style="display:block;width:1.5px;height:<?= $h2 ?>;background:<?= $color2 ?>;"></span>
                                        <?= $label2 ?>
                                    </span>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ========================================== -->
            <!-- 5. ACCOUNT TAB (ADMIN MANAGEMENT)          -->
            <!-- ========================================== -->
            <section id="tab-account" class="tab-section <?= ($page === 'account') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Admin Account Management</h1>
                    
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <button class="btn-modern-outline" onclick="handleLogout()">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <polyline points="16 17 21 12 16 7"></polyline>
                                <line x1="21" y1="12" x2="9" y2="12"></line>
                            </svg>
                            Logout
                        </button>
                        <button class="btn-modern-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add New Administrator
                        </button>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Current Admin Profile Card -->
                    <div class="col-12 col-lg-5">
                        <div class="white-panel">
                            <div class="d-flex align-items-center gap-3 mb-4 pb-3 border-bottom">
                                <div class="user-avatar-circle" style="width: 60px; height: 60px; font-size: 1.5rem;" id="accountAvatarBig">
                                    <?= strtoupper(substr($admin_name, 0, 1)) ?>
                                </div>
                                <div>
                                    <h4 class="mb-1" id="accountNameTitle"><?= $admin_name ?></h4>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">Master Station Administrator</span>
                                </div>
                            </div>

                            <form id="profileUpdateForm" onsubmit="handleProfileUpdate(event)">
                                <div class="row g-2 mb-3">
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small fw-bold">First Name</label>
                                        <input type="text" class="form-control" id="profFirstName" value="<?= htmlspecialchars(explode(' ', $admin_name)[0] ?? '') ?>" required>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label class="form-label small fw-bold">Last Name</label>
                                        <input type="text" class="form-control" id="profLastName" value="<?= htmlspecialchars(explode(' ', $admin_name)[1] ?? '') ?>" required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Email Address</label>
                                    <input type="email" class="form-control" id="profEmail" value="<?= $admin_email ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label small fw-bold">Username</label>
                                    <input type="text" class="form-control" id="profUsername" value="<?= $admin_username ?>" required>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label small fw-bold">Update Password (leave blank to keep current)</label>
                                    <input type="password" class="form-control" id="profPassword" placeholder="••••••••">
                                </div>

                                <button type="submit" class="btn-modern-primary w-100 justify-content-center">
                                    Update Admin Profile
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- System Users List -->
                    <div class="col-12 col-lg-7 d-flex">
                        <div class="white-panel flex-fill">
                            <div class="panel-header-row">
                                <div>
                                    <h4 class="panel-title">System Administrator Accounts</h4>
                                    <!-- <p class="panel-subtitle">Accounts authorized to configure water thresholds and control the pump</p> -->
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Created</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userListTableBody">
                                        <tr>
                                            <td>1</td>
                                            <td><strong><?= $admin_name ?></strong></td>
                                            <td>@<?= $admin_username ?></td>
                                            <td><?= $admin_email ?></td>
                                            <td>Active</td>
                                            <td><span class="badge bg-light text-primary border">Master</span></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </section>

        </div>
    </main>

    <!-- Bootstrap Modal: Add New Administrator (Design matches Update Admin Profile) -->
    <div class="modal fade" id="addAdminModal" tabindex="-1" aria-labelledby="addAdminModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 44px; height: 44px; background: rgba(2, 132, 199, 0.12); color: #0284c7;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="8.5" cy="7" r="4"></circle>
                                <line x1="20" y1="8" x2="20" y2="14"></line>
                                <line x1="23" y1="11" x2="17" y2="11"></line>
                            </svg>
                        </div>
                        <div>
                            <h5 class="modal-title fw-bold text-dark mb-0" id="addAdminModalLabel">Add New Administrator</h5>
                            <p class="text-muted small mb-0">Create new credentials for station system access</p>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form id="addAdminForm" onsubmit="handleAddAdmin(event)">
                        <div class="row g-2 mb-3">
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-bold">First Name</label>
                                <input type="text" class="form-control" id="addFirstName" placeholder="Juan" required>
                            </div>
                            <div class="col-12 col-sm-6">
                                <label class="form-label small fw-bold">Last Name</label>
                                <input type="text" class="form-control" id="addLastName" placeholder="Dela Cruz" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" class="form-control" id="addEmail" placeholder="juan.delacruz@example.com" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Username</label>
                            <input type="text" class="form-control" id="addUsername" placeholder="juandc" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" class="form-control" id="addPassword" placeholder="••••••••" required minlength="4">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" class="btn-modern-outline w-50 justify-content-center" data-bs-dismiss="modal">
                                Cancel
                            </button>
                            <button type="submit" class="btn-modern-primary w-50 justify-content-center" id="btnAddAdminSubmit">
                                Create Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- BOOTSTRAP 5 JS BUNDLE -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="bootstrap5/js/bootstrap.bundle.min.js"></script>

    <!-- CORE JAVASCRIPT APP LOGIC -->
    <script>
        // State variables
        let currentLevel = <?= (int)$current_water_level ?>;
        let pumpState = '<?= $pump_status ?>';
        let minThreshold = <?= (int)$min_threshold ?>;
        let maxThreshold = <?= (int)$max_threshold ?>;
        let dashboardBarChart = null;
        let trendChart = null;
        let distributionChart = null;

        // Mobile Sidebar Drawer Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebarNav');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.toggle('sidebar-open');
            if (backdrop) backdrop.classList.toggle('active');
        }

        function closeMobileSidebar() {
            const sidebar = document.getElementById('sidebarNav');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar) sidebar.classList.remove('sidebar-open');
            if (backdrop) backdrop.classList.remove('active');
        }

        // Logout Handler with SweetAlert Confirmation
        function handleLogout() {
            Swal.fire({
                title: 'Log out of AquaSync?',
                text: 'Are you sure you want to end your administrator session?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#64748b',
                confirmButtonText: 'Yes, Log Out',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'logout.php';
                }
            });
        }

        // Tab Switching
        function switchTab(tabName, event) {
            if (event) event.preventDefault();
            closeMobileSidebar();
            
            // Remove active from all nav items
            document.querySelectorAll('.nav-item-btn').forEach(btn => btn.classList.remove('active'));
            // Remove active from all sections
            document.querySelectorAll('.tab-section').forEach(sec => sec.classList.remove('active'));

            // Activate chosen nav and section
            const activeNav = document.getElementById('nav-' + tabName);
            const activeSection = document.getElementById('tab-' + tabName);
            if (activeNav) activeNav.classList.add('active');
            if (activeSection) activeSection.classList.add('active');

            // Update topbar section badge
            const labelMap = {
                'dashboard': 'REAL-TIME SENSOR &amp; PUMP MONITORING',
                'pumplogs':  'AUDIT TRAIL &amp; LOG ARCHIVE',
                'reports':   'METRICS &amp; PERFORMANCE',
                'settings':  'THRESHOLD CONFIGURATION',
                'account':   'USER ACCESS &amp; CREDENTIALS'
            };
            const badge = document.getElementById('topbarBreadcrumb');
            if (badge) {
                badge.innerHTML = '<span class="dot" style="width: 7px; height: 7px; border-radius: 50%; background-color: #0284c7 !important; display: inline-block; flex-shrink: 0;"></span> ' + (labelMap[tabName] || tabName.toUpperCase());
            }

            // Push state to URL without full reload
            if (history.pushState) {
                history.pushState(null, null, '?page=' + tabName);
            }

            // Tab-specific initializations
            if (tabName === 'dashboard') {
                setTimeout(updateDashboardBarChart, 50);
                fetchLiveTelemetry();
                loadDashboardRecentLogs(true);
            } else if (tabName === 'pumplogs') {
                loadPumpLogsTable();
            } else if (tabName === 'reports') {
                setTimeout(initReportsCharts, 100);
                loadWaterLevelsTable();
            } else if (tabName === 'account') {
                loadUsersList();
            }
        }

        // Clock updater
        function updateClock() {
            const now = new Date();
            const options = { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            const clockEl = document.getElementById('liveClockDisplay');
            if (clockEl) {
                clockEl.innerText = now.toLocaleDateString('en-US', options);
            }
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Initialize / Update Chart.js Bar Chart on Dashboard
        function updateDashboardBarChart() {
            const ctx = document.getElementById('dashboardBarChart');
            if (!ctx || typeof Chart === 'undefined') return;

            const textColor = '#64748b';
            const gridColor = '#f1f5f9';

            // Sample time series readings leading to the current reading
            const labels = ['06:00', '07:00', '08:00', '09:00', '10:00', '10:30', '11:00', 'Current'];
            const dataLevels = [95, 88, 74, 62, 95, 89, 78, currentLevel];

            // Color array: current reading gets vibrant electric blue (#0284c7)
            const backgroundColors = dataLevels.map((val, idx) => {
                if (idx === dataLevels.length - 1) return '#0284c7';
                return (val <= minThreshold) ? '#f59e0b' : '#38bdf8';
            });

            if (dashboardBarChart) {
                dashboardBarChart.data.datasets[0].data = dataLevels;
                dashboardBarChart.data.datasets[0].backgroundColor = backgroundColors;
                dashboardBarChart.data.datasets[1].data = labels.map(() => minThreshold);
                dashboardBarChart.data.datasets[2].data = labels.map(() => maxThreshold);
                dashboardBarChart.update();
                return;
            }

            dashboardBarChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Water Level (%)',
                            data: dataLevels,
                            backgroundColor: backgroundColors,
                            borderRadius: 8,
                            borderSkipped: false,
                            barPercentage: 0.55
                        },
                        {
                            label: `Min Threshold (${minThreshold}%)`,
                            data: labels.map(() => minThreshold),
                            type: 'line',
                            borderColor: '#f59e0b',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            pointRadius: 0
                        },
                        {
                            label: `Max Cutoff (${maxThreshold}%)`,
                            data: labels.map(() => maxThreshold),
                            type: 'line',
                            borderColor: '#ef4444',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            fill: false,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                color: textColor,
                                font: { size: 11, family: "'Plus Jakarta Sans', sans-serif", weight: '600' }
                            }
                        },
                        tooltip: {
                            backgroundColor: '#0f172a',
                            padding: 10,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ' + context.parsed.y + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            min: 0,
                            max: 100,
                            ticks: {
                                color: textColor,
                                stepSize: 20,
                                callback: val => val + '%'
                            },
                            grid: {
                                color: gridColor
                            }
                        },
                        x: {
                            ticks: {
                                color: textColor
                            },
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        }

        // Update dashboard telemetry and controls
        function renderDashboardTelemetry() {
            // Fallback pump state only if undefined
            if (!pumpState || (pumpState !== 'ON' && pumpState !== 'OFF')) {
                pumpState = (currentLevel <= minThreshold) ? 'ON' : 'OFF';
            }

            // Snapshot card 1
            const cardVal = document.getElementById('cardWaterLevelVal');
            if (cardVal) cardVal.innerText = currentLevel;

            const cardBar = document.getElementById('cardWaterLevelBar');
            if (cardBar) cardBar.style.width = currentLevel + '%';

            // Card status chip
            const statusChip = document.getElementById('cardWaterLevelStatus');
            if (statusChip) {
                if (currentLevel <= minThreshold) {
                    statusChip.className = 'stat-status-chip warning';
                    statusChip.innerText = 'Low (Refilling)';
                } else if (currentLevel >= maxThreshold) {
                    statusChip.className = 'stat-status-chip success';
                    statusChip.innerText = 'Full';
                } else {
                    statusChip.className = 'stat-status-chip info';
                    statusChip.innerText = 'Optimal';
                }
            }

            // Inflow label
            const refillInd = document.getElementById('tankRefillIndicator');
            if (refillInd) {
                refillInd.innerText = (pumpState === 'ON') ? 'Pump Refilling Active' : 'Pump Standby / Level Stable';
            }

            // Telemetry readings
            const liters = Math.round((currentLevel / 100) * 1000);
            if (document.getElementById('telemLevelPct')) document.getElementById('telemLevelPct').innerText = currentLevel + '%';
            if (document.getElementById('telemLiters')) document.getElementById('telemLiters').innerText = liters;

            // Pump state controls
            const pmpText = document.getElementById('cardPumpStatusText');
            if (pmpText) pmpText.innerText = pumpState;

            const pmpChip = document.getElementById('cardPumpChip');
            if (pmpChip) {
                pmpChip.className = (pumpState === 'ON') ? 'stat-status-chip success' : 'stat-status-chip info';
                pmpChip.innerText = (pumpState === 'ON') ? 'ACTIVE' : 'STANDBY';
            }

            const pmpBadge = document.getElementById('cardPumpBadge');
            if (pmpBadge) {
                pmpBadge.className = 'stat-icon-badge ' + ((pumpState === 'ON') ? 'emerald' : 'blue');
            }

            const pmpDesc = document.getElementById('cardPumpDesc');
            if (pmpDesc) {
                pmpDesc.innerText = (pumpState === 'ON') ? 'Refilling reservoir...' : `Standby (Refills at ≤${minThreshold}%)`;
            }

            const telemPmp = document.getElementById('telemPumpState');
            if (telemPmp) {
                telemPmp.innerText = (pumpState === 'ON') ? 'ACTIVE (ON)' : 'STANDBY (OFF)';
            }

            const pmpBanner = document.getElementById('pumpControlBanner');
            if (pmpBanner) {
                if (pumpState === 'ON') pmpBanner.classList.add('pumping');
                else pmpBanner.classList.remove('pumping');
            }

            const pmpLed = document.getElementById('pumpLedDot');
            if (pmpLed) {
                if (pumpState === 'ON') pmpLed.classList.add('active');
                else pmpLed.classList.remove('active');
            }

            const pmpCtrlTitle = document.getElementById('pumpControlTitle');
            if (pmpCtrlTitle) pmpCtrlTitle.innerText = `Water Pump Relay: ${pumpState}`;

            const pmpCtrlSub = document.getElementById('pumpControlSub');
            if (pmpCtrlSub) {
                pmpCtrlSub.innerText = (pumpState === 'ON') ? 'Actively refilling the water tank' : 'Motor in standby mode';
            }

            const btnToggle = document.getElementById('btnTogglePumpManual');
            if (btnToggle) {
                btnToggle.innerText = (pumpState === 'ON') ? 'Stop Pump' : 'Start Pump';
            }

            // Update the Chart.js Bar Graph
            updateDashboardBarChart();
        }

        // Render Recent Logs table on dashboard (powered by pump_logs VIEW, limit 5)
        function renderRecentLogs(logs) {
            const tbody = document.getElementById('dashboardRecentLogsBody');
            if (!tbody) return;

            if (!logs || logs.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No pump operations recorded yet.</td></tr>`;
                return;
            }

            let html = '';
            logs.slice(0, 5).forEach(log => {
                const isON = (log.action === 'ON');
                const badge = isON
                    ? `<span class="badge-pump-on">ON</span>`
                    : `<span class="badge-pump-off">OFF</span>`;

                const desc = log.description || (isON ? 'Water level dropped' : 'Target level achieved');

                html += `
                    <tr class="log-row">
                        <td><strong>#${log.log_id}</strong></td>
                        <td>${badge}</td>
                        <td class="fw-bold">${log.water_level}%</td>
                        <td>${desc}</td>
                        <td class="text-muted small">${log.created_at}</td>
                    </tr>
                `;
            });
            tbody.innerHTML = html;
        }

        // Dedicated AJAX Loader for Dashboard Recent Refill Operations (Limit 5 from pump_logs VIEW)
        function loadDashboardRecentLogs(isSilent = false) {
            const tbody = document.getElementById('dashboardRecentLogsBody');
            if (tbody && !isSilent && tbody.children.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Loading recent pump operations...</td></tr>`;
            }

            $.ajax({
                url: 'fetch_from_database_api.php',
                method: 'GET',
                data: { type: 'pump_logs', limit: 5 },
                dataType: 'json'
            })
            .done(data => {
                if (data && data.success && data.data) {
                    const payload = data.data;
                    const logs = Array.isArray(payload) ? payload : (payload.logs || []);
                    renderRecentLogs(logs);
                }
            })
            .fail(err => {
                console.error('AJAX error loading dashboard recent operations:', err);
            });
        }

        // Fetch Live Telemetry from database API
        function fetchLiveTelemetry() {
            $.ajax({
                url: 'fetch_from_database_api.php',
                method: 'GET',
                data: { type: 'live_snapshot' },
                dataType: 'json'
            })
                .done(data => {
                    if (data && data.success && data.data) {
                        const d = data.data;

                        // Real-time metric cards based on the latest database insert
                        if (d.current_water_level !== undefined) {
                            currentLevel = parseInt(d.current_water_level);
                        }
                        if (d.pump_status) {
                            pumpState = d.pump_status;
                        }
                        if (d.min_threshold) {
                            minThreshold = parseInt(d.min_threshold);
                        }
                        if (d.max_threshold) {
                            maxThreshold = parseInt(d.max_threshold);
                        }

                        // Refill cycles and pumped volume from database
                        const cyclesVal = (d.cycles_today !== undefined && d.cycles_today !== null) ? d.cycles_today : 0;
                        const volumeVal = (d.total_volume_today !== undefined && d.total_volume_today !== null) ? d.total_volume_today : (cyclesVal * 350);

                        if (document.getElementById('cardCyclesCount')) document.getElementById('cardCyclesCount').innerText = cyclesVal;
                        if (document.getElementById('heroCycleCount')) document.getElementById('heroCycleCount').innerText = cyclesVal;
                        if (document.getElementById('cardPumpedVolume')) document.getElementById('cardPumpedVolume').innerText = Number(volumeVal).toLocaleString();

                        // Water level card
                        if (document.getElementById('cardWaterLevelVal')) document.getElementById('cardWaterLevelVal').innerText = currentLevel;
                        if (document.getElementById('cardWaterLevelBar')) document.getElementById('cardWaterLevelBar').style.width = currentLevel + '%';

                        // Water pump status card
                        if (document.getElementById('cardPumpStatusText')) document.getElementById('cardPumpStatusText').innerText = pumpState;

                        // Threshold limits card
                        if (document.getElementById('cardMinThresh')) document.getElementById('cardMinThresh').innerText = minThreshold;
                        if (document.getElementById('cardMaxThresh')) document.getElementById('cardMaxThresh').innerText = maxThreshold;
                        if (document.getElementById('heroMinThresh')) document.getElementById('heroMinThresh').innerText = minThreshold;
                        if (document.getElementById('heroMaxThresh')) document.getElementById('heroMaxThresh').innerText = maxThreshold;
                        if (document.getElementById('cardHysteresis')) document.getElementById('cardHysteresis').innerText = (maxThreshold - minThreshold);

                        renderDashboardTelemetry();

                        // Update Recent Refill Operations table on dashboard
                        if (d.recent_logs) {
                            renderRecentLogs(d.recent_logs);
                        }
                    } else {
                        console.error('Telemetry request returned an unsuccessful response:', data);
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Telemetry request failed:', status, error);
                });
        }

        // Load Full Pump Logs table for Pump Logs Tab (ordered: Latest to Oldest)
        function loadPumpLogsTable(isSilent = false) {
            const filter = document.getElementById('logFilterAction')?.value || '';
            const tbody = document.getElementById('pumpLogsTableBody');
            if (tbody && !isSilent && tbody.children.length === 0) {
                tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">Loading logs...</td></tr>`;
            }

            $.ajax({
                url: 'fetch_from_database_api.php',
                method: 'GET',
                data: { type: 'pump_logs', action: filter },
                dataType: 'json'
            })
                .done(data => {
                    if (data && data.success && data.data) {
                        const payload     = data.data;
                        const logs        = Array.isArray(payload) ? payload : (payload.logs || []);
                        const logMinThr   = Array.isArray(payload) ? minThreshold : (payload.min_threshold ?? minThreshold);
                        const logMaxThr   = Array.isArray(payload) ? maxThreshold : (payload.max_threshold ?? maxThreshold);
                        const pumpIsOn    = (pumpState === 'ON');
                        const curLevel    = currentLevel;

                        if (logs.length === 0) {
                            if (tbody) tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-muted">No matching logs found.</td></tr>`;
                            return;
                        }

                        let html = '';
                        logs.forEach((log, index) => {
                            const isON = (log.action === 'ON');
                            const badge = isON
                                ? `<span class="badge-pump-on">ON</span>`
                                : `<span class="badge-pump-off">OFF</span>`;

                            let desc = log.description || (isON ? 'Water level dropped' : 'Target level achieved');

                            html += `
                                <tr class="log-row">
                                    <td><strong>#${log.log_id}</strong></td>
                                    <td>${badge}</td>
                                    <td class="fw-bold">${log.water_level}%</td>
                                    <td>${desc}</td>
                                    <td class="text-muted small">${log.created_at}</td>
                                </tr>
                            `;
                        });
                        if (tbody) tbody.innerHTML = html;
                        const countBadge = document.getElementById('logsCountBadge');
                        if (countBadge) countBadge.innerText = `${logs.length} Log entries loaded`;
                        filterLogRows();
                    } else {
                        if (!isSilent && tbody) {
                            tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Unable to load pump logs.</td></tr>`;
                        }
                    }
                })
                .fail((xhr, status, error) => {
                    if (!isSilent && tbody) {
                        tbody.innerHTML = `<tr><td colspan="5" class="text-center py-4 text-danger">Unable to load pump logs.</td></tr>`;
                    }
                });
        }

        // Filter log rows by search query
        function filterLogRows() {
            const query = (document.getElementById('logSearchQuery')?.value || '').toLowerCase();
            document.querySelectorAll('#pumpLogsTableBody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(query) ? '' : 'none';
            });
        }

        // Export pump logs to CSV
        function exportLogsToCSV() {
            let csv = "Log ID,Action,Water Level,Timestamp\n";
            document.querySelectorAll('#pumpLogsTableBody tr').forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 5) {
                    const id = cols[0].innerText.replace('#', '').trim();
                    const action = cols[1].innerText.trim();
                    const level = cols[2].innerText.trim();
                    const time = cols[4].innerText.trim();
                    csv += `"${id}","${action}","${level}","${time}"\n`;
                }
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'aquasync_pump_logs.csv');
            a.click();
        }

        // Load Water Levels History table for Reports Tab (ordered: Latest to Oldest)
        function loadWaterLevelsTable() {
            const tbody = document.getElementById('reportsWaterLevelsBody');
            if (!tbody) return;

            $.ajax({
                url: 'fetch_from_database_api.php',
                method: 'GET',
                data: { type: 'water_levels', limit: 100 },
                dataType: 'json'
            })
            .done(data => {
                if (data && data.success && data.data) {
                    const records = data.data;
                    if (records.length === 0) {
                        tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-muted">No water level records found.</td></tr>`;
                        return;
                    }
                    let html = '';
                    records.forEach(r => {
                        const isON = (r.action === 'ON');
                        const actionBadge = isON
                            ? `<span class="badge-pump-on">ON</span>`
                            : `<span class="badge-pump-off">OFF</span>`;

                        const minT = r.min_threshold || minThreshold;
                        const maxT = r.max_threshold || maxThreshold;
                        const desc = r.description || (isON ? `Level dropped to ${r.water_level}% (<= min threshold ${minT}%). Pump turned ON.` : `Level reached ${r.water_level}%. Pump is OFF.`);

                        html += `
                            <tr class="water-level-row">
                                <td><strong>#${r.level_id}</strong></td>
                                <td class="fw-bold">${r.water_level}%</td>
                                <td>${actionBadge}</td>
                                <td class="text-muted small">${minT}% - ${maxT}%</td>
                                <td>${desc}</td>
                                <td class="text-muted small">${r.recorded_at}</td>
                            </tr>
                        `;
                    });
                    tbody.innerHTML = html;
                    const countBadge = document.getElementById('waterLevelsCountBadge');
                    if (countBadge) countBadge.innerText = `${records.length} Records (Latest to Oldest)`;
                } else {
                    tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Unable to load records.</td></tr>`;
                }
            })
            .fail(() => {
                tbody.innerHTML = `<tr><td colspan="6" class="text-center py-4 text-danger">Unable to load records.</td></tr>`;
            });
        }

        // Export Water Levels to CSV
        function exportWaterLevelsToCSV() {
            let csv = "Level ID,Water Level (%),Pump Action,Threshold Limits,Event Description,Recorded Timestamp\n";
            document.querySelectorAll('#reportsWaterLevelsBody tr').forEach(row => {
                const cols = row.querySelectorAll('td');
                if (cols.length >= 6) {
                    const id = cols[0].innerText.replace('#', '').trim();
                    const level = cols[1].innerText.replace('%', '').trim();
                    const action = cols[2].innerText.trim();
                    const thresh = cols[3].innerText.trim();
                    const desc = cols[4].innerText.replace(/"/g, '""').trim();
                    const time = cols[5].innerText.trim();
                    csv += `"${id}","${level}","${action}","${thresh}","${desc}","${time}"\n`;
                }
            });

            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.setAttribute('href', url);
            a.setAttribute('download', 'aquasync_water_levels.csv');
            a.click();
        }

        // Threshold slider controls
        function updateThresholdSliders(newMin, newMax) {
            if (newMin !== null) {
                minThreshold = parseInt(newMin);
                const minEl = document.getElementById('minThreshDisplay');
                if (minEl) minEl.innerText = minThreshold;
                const minLit = document.getElementById('minLitersDisplay');
                if (minLit) minLit.innerText = Math.round((minThreshold / 100) * 1000);
            }
            if (newMax !== null) {
                maxThreshold = parseInt(newMax);
                const maxEl = document.getElementById('maxThreshDisplay');
                if (maxEl) maxEl.innerText = maxThreshold;
                const maxLit = document.getElementById('maxLitersDisplay');
                if (maxLit) maxLit.innerText = Math.round((maxThreshold / 100) * 1000);
            }

            const span = maxThreshold - minThreshold;
            const hSpan = document.getElementById('hysteresisSpanDisplay');
            if (hSpan) hSpan.innerText = span;
            const hLit = document.getElementById('hysteresisLitersDisplay');
            if (hLit) hLit.innerText = Math.round((span / 100) * 1000);

            // Update visual hysteresis range marks and bars
            if (document.getElementById('visualMinMark')) document.getElementById('visualMinMark').innerText = minThreshold;
            if (document.getElementById('visualMaxMark')) document.getElementById('visualMaxMark').innerText = maxThreshold;
            if (document.getElementById('visualTriggerBar')) document.getElementById('visualTriggerBar').style.width = minThreshold + '%';
            if (document.getElementById('visualBufferBar')) document.getElementById('visualBufferBar').style.width = Math.max(0, span) + '%';
            if (document.getElementById('visualTargetBar')) document.getElementById('visualTargetBar').style.width = Math.max(0, 100 - maxThreshold) + '%';

            updateDashboardBarChart();
        }

        function saveThresholdSettings() {
            if (minThreshold >= maxThreshold) {
                Swal.fire('Invalid Thresholds', 'Minimum threshold must be lower than maximum threshold.', 'error');
                return;
            }

            $.ajax({
                url: 'insert_to_database_api.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_thresholds',
                    min_threshold: minThreshold,
                    max_threshold: maxThreshold
                }
            })
            .done(data => {
                if (!data || !data.success) {
                    Swal.fire('Unable to Save Thresholds', data?.message || 'Threshold settings were not saved.', 'error');
                    return;
                }
                Swal.fire({
                    icon: 'success',
                    title: 'Thresholds Saved',
                    text: data.message || `Min: ${minThreshold}% | Max: ${maxThreshold}% are now stored in the database.`,
                    confirmButtonColor: '#0284c7'
                });
                fetchLiveTelemetry();
            })
            .fail((xhr, status, error) => {
                console.error('Threshold update request failed:', status, error);
                Swal.fire('Unable to Save Thresholds', 'Threshold settings could not be saved. Please try again.', 'error');
            });
        }

        // User profile update
        function handleProfileUpdate(e) {
            e.preventDefault();
            const first = document.getElementById('profFirstName').value;
            const last = document.getElementById('profLastName').value;
            const email = document.getElementById('profEmail').value;
            const user = document.getElementById('profUsername').value;
            const pass = document.getElementById('profPassword').value;

            $.ajax({
                url: 'insert_to_database_api.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'update_user',
                    user_id: 1,
                    first_name: first,
                    last_name: last,
                    email: email,
                    username: user,
                    password: pass
                }
            })
            .done(data => {
                if (!data || !data.success) {
                    Swal.fire('Unable to Update Profile', data?.message || 'Profile changes were not saved.', 'error');
                    return;
                }
                Swal.fire('Profile Updated', data.message || 'Administrator profile details updated successfully.', 'success');
                document.getElementById('sidebarUserName').innerText = first + ' ' + last;
                document.getElementById('sidebarUserEmail').innerText = email;
                document.getElementById('accountNameTitle').innerText = first + ' ' + last;
                document.getElementById('heroUserName').innerText = first;
                document.getElementById('sidebarAvatar').innerText = first.charAt(0).toUpperCase();
                document.getElementById('accountAvatarBig').innerText = first.charAt(0).toUpperCase();
            })
            .fail((xhr, status, error) => {
                console.error('Profile update request failed:', status, error);
                Swal.fire('Unable to Update Profile', 'Profile changes could not be saved. Please try again.', 'error');
            });
        }

        function loadUsersList() {
            $.ajax({
                url: 'fetch_from_database_api.php',
                method: 'GET',
                data: { type: 'users' },
                dataType: 'json'
            })
                .done(data => {
                    if (data && data.success && data.data) {
                        const tbody = document.getElementById('userListTableBody');
                        if (!tbody) return;
                        let html = '';
                        data.data.forEach(u => {
                            html += `
                                <tr>
                                    <td>${u.user_id}</td>
                                    <td><strong>${u.first_name} ${u.last_name}</strong></td>
                                    <td>@${u.username}</td>
                                    <td>${u.email}</td>
                                    <td>${u.created_at || 'Active'}</td>
                                    <td><span class="badge bg-light text-primary border">Master</span></td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    } else {
                        console.error('Users request returned an unsuccessful response:', data);
                    }
                })
                .fail((xhr, status, error) => {
                    console.error('Users request failed:', status, error);
                    Swal.fire('Unable to Load Users', 'Administrator accounts could not be loaded.', 'error');
                });
        }

        function openAddUserModal() {
            const modalEl = document.getElementById('addAdminModal');
            if (modalEl) {
                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal.show();
            }
        }

        function handleAddAdmin(e) {
            e.preventDefault();
            const firstName = document.getElementById('addFirstName').value.trim();
            const lastName = document.getElementById('addLastName').value.trim();
            const email = document.getElementById('addEmail').value.trim();
            const username = document.getElementById('addUsername').value.trim();
            const password = document.getElementById('addPassword').value.trim();

            if (!firstName || !lastName || !email || !username || !password) {
                Swal.fire('Incomplete Details', 'Please complete all required fields.', 'warning');
                return;
            }

            const submitBtn = document.getElementById('btnAddAdminSubmit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>Saving...';
            }

            $.ajax({
                url: 'insert_to_database_api.php',
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'add_user',
                    first_name: firstName,
                    last_name: lastName,
                    email: email,
                    username: username,
                    password: password
                }
            })
            .done(data => {
                if (!data || !data.success) {
                    Swal.fire('Unable to Create Account', data?.message || 'The administrator account could not be created.', 'error');
                    return;
                }

                // Hide modal
                const modalEl = document.getElementById('addAdminModal');
                if (modalEl) {
                    const modalInstance = bootstrap.Modal.getInstance(modalEl);
                    if (modalInstance) {
                        modalInstance.hide();
                    }
                }

                // Reset form
                document.getElementById('addAdminForm').reset();

                Swal.fire({
                    title: 'Account Created',
                    text: data.message || `Administrator @${username} registered successfully.`,
                    icon: 'success',
                    confirmButtonColor: '#0284c7'
                });

                loadUsersList();
            })
            .fail((xhr, status, error) => {
                console.error('Create user request failed:', status, error);
                Swal.fire('Unable to Create Account', 'The administrator account could not be created.', 'error');
            })
            .always(() => {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Account';
                }
            });
        }

        // Initialize Reports Charts (Chart.js)
        function initReportsCharts() {
            if (typeof Chart === 'undefined') return;

            const textColor = '#64748b';
            const gridColor = '#f1f5f9';

            // Chart 1: Water Level Trend
            const trendCtx = document.getElementById('waterTrendChart');
            if (trendCtx) {
                if (trendChart) trendChart.destroy();
                trendChart = new Chart(trendCtx, {
                    type: 'line',
                    data: {
                        labels: ['06:00', '07:00', '08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00'],
                        datasets: [
                            {
                                label: 'Water Level (%)',
                                data: [95, 88, 72, 60, 95, 84, 60, 95, 87, 85],
                                borderColor: '#0284c7',
                                backgroundColor: 'rgba(2, 132, 199, 0.12)',
                                fill: true,
                                tension: 0.35,
                                pointBackgroundColor: '#0284c7',
                                pointRadius: 4
                            },
                            {
                                label: `Min Threshold (${minThreshold}%)`,
                                data: [minThreshold, minThreshold, minThreshold, minThreshold, minThreshold, minThreshold, minThreshold, minThreshold, minThreshold, minThreshold],
                                borderColor: '#f59e0b',
                                borderDash: [5, 5],
                                fill: false,
                                pointRadius: 0
                            },
                            {
                                label: `Max Cutoff (${maxThreshold}%)`,
                                data: [maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold, maxThreshold],
                                borderColor: '#ef4444',
                                borderDash: [5, 5],
                                fill: false,
                                pointRadius: 0
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: { color: textColor }
                            }
                        },
                        scales: {
                            y: {
                                min: 0,
                                max: 100,
                                ticks: { color: textColor, callback: v => v + '%' },
                                grid: { color: gridColor }
                            },
                            x: {
                                ticks: { color: textColor },
                                grid: { display: false }
                            }
                        }
                    }
                });
            }

            // Chart 2: Refill Distribution
            const distCtx = document.getElementById('refillDistributionChart');
            if (distCtx) {
                if (distributionChart) distributionChart.destroy();
                distributionChart = new Chart(distCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Morning (6-11 AM)', 'Midday (11 AM-3 PM)', 'Afternoon (3-7 PM)', 'Night (7-11 PM)'],
                        datasets: [{
                            data: [35, 45, 15, 5],
                            backgroundColor: ['#38bdf8', '#0284c7', '#0369a1', '#cbd5e1']
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: { color: textColor }
                            }
                        }
                    }
                });
            }
        }

        // Auto polling interval (real-time monitoring and auto-refresh via AJAX/jQuery)
        setInterval(() => {
            const currentTab = document.querySelector('.tab-section.active')?.id;
            if (currentTab === 'tab-dashboard') {
                fetchLiveTelemetry();
                loadDashboardRecentLogs(true);
            } else if (currentTab === 'tab-pumplogs') {
                loadPumpLogsTable(true);
            } else if (currentTab === 'tab-reports') {
                loadWaterLevelsTable();
            }
        }, 2000);

        // Initial setup on load
        document.addEventListener('DOMContentLoaded', () => {
            renderDashboardTelemetry();
            fetchLiveTelemetry();
            loadDashboardRecentLogs(true);
            if ('<?= $page ?>' === 'pumplogs') {
                loadPumpLogsTable();
            } else if ('<?= $page ?>' === 'reports') {
                loadWaterLevelsTable();
            }
        });
    </script>
</body>
</html>
