
            <section id="tab-account" class="tab-section <?= ($page === 'account') ? 'active' : '' ?>">
                <div class="section-header-row">
                    <div>
                        <h1 class="section-main-heading">Admin Account Management</h1>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Manage administrator credentials and system access permissions</p>
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
                        <?php if ($current_user_role === 'master'): ?>
                        <button class="btn-modern-primary" data-bs-toggle="modal" data-bs-target="#addAdminModal">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add New Administrator
                        </button>
                        <?php endif; ?>
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
                                    <h4 class="mb-1" id="accountNameTitle"><?= htmlspecialchars($admin_name) ?></h4>
                                    <span class="badge bg-primary-subtle text-primary fw-bold">
                                        <?= ($current_user_role === 'master') ? 'Master Station Administrator' : 'Station Administrator' ?>
                                    </span>
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
                                    
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="modern-table">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="userListTableBody">
                                        <tr>
                                            <td><strong><?= htmlspecialchars($admin_name) ?></strong></td>
                                            <td>@<?= htmlspecialchars($admin_username) ?></td>
                                            <td><?= htmlspecialchars($admin_email) ?></td>
                                            <td><span class="badge <?= ($current_user_role === 'master') ? 'bg-primary-subtle text-primary border border-primary-subtle' : 'bg-warning-subtle text-warning border border-warning-subtle' ?>"><?= ($current_user_role === 'master') ? 'Master' : 'Admin' ?></span></td>
                                            <td><button type="button" class="btn btn-sm btn-outline-secondary" disabled>Delete</button></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div>
                </div>
            </section>
