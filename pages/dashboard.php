
            <section id="tab-dashboard" class="tab-section <?= ($page === 'dashboard') ? 'active' : '' ?>">
                <!-- Title Header Row -->
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Water Tank & System Status</h1>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Real-time water level monitoring, pump status, and automatic refill overview</p>
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
                <?php
                $hero_greetings = [
                    'Welcome back',
                    'Hey there',
                    'Nice to see you',
                    "Glad you're here",
                    'Welcome to AquaSync'
                ];
                $random_greeting = $hero_greetings[array_rand($hero_greetings)];
                ?>
                <div class="hero-banner-card">
                    <div class="hero-content-col">
                        <h2 class="hero-title"><span id="heroGreetingText"><?= htmlspecialchars($random_greeting) ?></span>, <span id="heroUserName"><?= htmlspecialchars(explode(' ', $admin_name)[0]) ?></span></h2>
                        <p class="hero-subtitle">
                            AquaSync Automated Water Refilling &amp; Monitoring System — real-time ESP32 water level sensor with automatic pump control.
                        </p>
                        <div class="hero-action-pills d-flex flex-wrap gap-2">
                            <span class="pill-stat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <rect x="4" y="4" width="16" height="16" rx="2"></rect>
                                    <rect x="9" y="9" width="6" height="6"></rect>
                                </svg>
                                ESP32 + WATER LEVEL SENSOR
                            </span>
                            <span class="pill-stat">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                                </svg>
                                AUTOMATED REFILL CONTROLLER
                            </span>
                        </div>
                    </div>
                </div>

               
                <div class="snapshot-title-row">
                    <div>
                        <h3 class="snapshot-heading mb-1">Operational Metrics Snapshot</h3>
                        <p class="snapshot-sub mb-0">Live sensor telemetry and threshold indicators from the latest database record</p>
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
