            <!-- ========================================== -->
            <!-- 3. REPORTS TAB (ANALYTICS)                 -->
            <!-- ========================================== -->
            <section id="tab-reports" class="tab-section <?= ($page === 'reports') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Water Station Analytics & Reports</h1>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">24-hour refill trends, hourly pump distribution, and historical sensor archive</p>
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
                <div class="row g-4 mb-4 reports-charts-row">
                    <!-- Left Chart: Water Level Trend Over Time -->
                    <div class="col-12 col-lg-8">
                        <div class="white-panel h-100">
                            <div class="panel-header-row">
                                <div>
                                    <h4 class="panel-title">Water Level Sensor History & Refill Curve</h4>
                                    <p class="panel-subtitle">Sensor readings recorded across refill cycles</p>
                                </div>
                            </div>
                            <div class="reports-chart-container" style="height: 320px; position: relative;">
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
                            <div class="reports-chart-container reports-doughnut-container" style="height: 320px; position: relative;">
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
