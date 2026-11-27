
            <section id="tab-settings" class="tab-section <?= ($page === 'settings') ? 'active' : '' ?>">
                <div class="section-header-row mb-4">
                    <div>
                        <h1 class="section-main-heading">Settings &amp; Threshold Configuration</h1>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Configure automatic pump trigger and cutoff water level thresholds</p>
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
                            <div class="mb-4">
                                <h4 class="panel-title mb-1">Auto Refill Thresholds</h4>
                                <p class="panel-subtitle mb-0">Set minimum pump ON trigger and maximum pump OFF cutoff levels</p>
                            </div>

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
                                <!-- Tick marks: major every 2%, minor every 1% (matches min slider's 11-tick layout) | Range: 80–90 = 10 units -->
                                <div class="slider-ticks" style="position:relative;width:100%;height:18px;margin-top:2px;">
                                    <?php
                                    // Max slider: 80 to 90, step 1 (major on even %, minor on odd %)
                                    $minS2 = 80; $maxS2 = 90; $range2 = $maxS2 - $minS2;
                                    for ($v2 = $minS2; $v2 <= $maxS2; $v2 += 1):
                                        $pct2 = ($v2 - $minS2) / $range2 * 100;
                                        $isMajor2 = ($v2 % 2 === 0);
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

                <!-- Settings History Table -->
                <div class="row g-4 mt-1">
                    <div class="col-12">
                        <div class="white-panel">
                            <div class="panel-header-row">
                                <div>
                                    <h4 class="panel-title mb-1">Settings History</h4>
                                    <p class="panel-subtitle mb-0">Audit log of all previous water level threshold changes</p>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Min Threshold Change</th>
                                            <th>Max Threshold Change</th>
                                            <th>Changed By</th>
                                            <th>Role</th>
                                            <th>Changed At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="settingsHistoryTableBody">
                                        <?php if (!empty($settings_history_rows)): ?>
                                            <?php foreach ($settings_history_rows as $h):
                                                $hName = trim(($h['first_name'] ?? '') . ' ' . ($h['last_name'] ?? ''));
                                                $hUsername = $h['username'] ?? null;
                                                $hRole = isset($h['role']) ? strtolower(trim($h['role'])) : null;
                                                $hTime = !empty($h['changed_at']) ? date('M d, Y h:i A', strtotime($h['changed_at'])) : '—';
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= (int)$h['old_min_threshold'] ?>%</span>
                                                    <span class="text-muted mx-1">&rarr;</span>
                                                    <span class="badge bg-primary-subtle text-primary fw-bold"><?= (int)$h['new_min_threshold'] ?>%</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary-subtle text-secondary fw-semibold"><?= (int)$h['old_max_threshold'] ?>%</span>
                                                    <span class="text-muted mx-1">&rarr;</span>
                                                    <span class="badge bg-success-subtle text-success fw-bold"><?= (int)$h['new_max_threshold'] ?>%</span>
                                                </td>
                                                <td>
                                                    <?php if ($hName !== ''): ?>
                                                        <strong><?= htmlspecialchars($hName) ?></strong>
                                                        <?php if ($hUsername): ?>
                                                            <span class="text-muted small">(@<?= htmlspecialchars($hUsername) ?>)</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">System / Unknown</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($hRole): ?>
                                                        <span class="badge <?= ($hRole === 'master') ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?>">
                                                            <?= ($hRole === 'master') ? 'Master' : 'Admin' ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-muted">&mdash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= htmlspecialchars($hTime) ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-4">No threshold changes recorded yet.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
