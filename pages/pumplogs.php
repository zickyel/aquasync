
            <section id="tab-pumplogs" class="tab-section <?= ($page === 'pumplogs') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Pump Logs & Refill History</h1>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Complete audit trail of automatic pump ON/OFF activations and water level events</p>
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
