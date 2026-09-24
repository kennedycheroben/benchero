<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-4 bg-dark text-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-danger px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">
                    <?= htmlspecialchars($user['role_display'] ?? 'Platform Admin') ?>
                </span>
                <h2 class="fw-bold mb-0">Benchero Platform Administration</h2>
            </div>
            <span class="text-white-50"><i class="bi bi-person-circle me-1"></i>Logged in as <strong><?= htmlspecialchars($user['name']) ?></strong></span>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Website & Platform Statistics Cards -->
        <h4 class="fw-bold mb-3"><i class="bi bi-graph-up-arrow text-primary me-2"></i>Platform Overview & Website Statistics</h4>
        <div class="row g-4 mb-5">
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-primary border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Organizations</div>
                    <div class="display-6 fw-black text-primary"><?= number_format($stats['orgs']) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-success border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Users</div>
                    <div class="display-6 fw-black text-success"><?= number_format($stats['users']) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-warning border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Sports Matches</div>
                    <div class="display-6 fw-black text-warning"><?= number_format($sportsMatchesCount ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-danger border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Sports News</div>
                    <div class="display-6 fw-black text-danger"><?= number_format($sportsNewsCount ?? 0) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-warning border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Revenue</div>
                    <div class="display-6 fw-black text-warning">KES <?= number_format($stats['revenue'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-4 col-lg-2-4">
                <div class="p-4 bg-white rounded-4 shadow-sm border border-start border-danger border-4">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Contact Messages</div>
                    <div class="display-6 fw-black text-danger">
                        <?= number_format($stats['contact_messages']) ?>
                        <?php if ($stats['unread_messages'] > 0): ?>
                            <span class="fs-7 badge bg-danger text-white align-middle ms-1"><?= $stats['unread_messages'] ?> unread</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Role Selection & Permissions Management -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-shield-lock text-primary me-2"></i>User Role & Statistics Permissions Management</h5>
                    <p class="text-muted small mb-0">Select who can be Super Admin, Platform Admin, Club Owner, or Member to control access to platform website statistics.</p>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>User Details</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Statistics Access</th>
                            <th>Registered</th>
                            <th class="text-end">Assign Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                    <small class="text-muted">ID: <code><?= htmlspecialchars($u['id']) ?></code></small>
                                </td>
                                <td class="fw-semibold text-primary"><?= htmlspecialchars($u['email']) ?></td>
                                <td>
                                    <?php
                                        $roleName = $u['role_name'] ?? ($u['is_platform_admin'] ? 'super_admin' : 'club_owner');
                                        $roleDisplay = $u['role_display'] ?? ($u['is_platform_admin'] ? 'Super Admin' : 'Club Owner');
                                        $badgeClass = match($roleName) {
                                            'super_admin' => 'bg-danger',
                                            'admin' => 'bg-warning text-dark',
                                            'club_owner' => 'bg-primary',
                                            default => 'bg-secondary'
                                        };
                                    ?>
                                    <span class="badge <?= $badgeClass ?> px-3 py-2 fs-7 text-uppercase fw-bold">
                                        <?= htmlspecialchars($roleDisplay) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($u['is_platform_admin'] || in_array($roleName, ['super_admin', 'admin'])): ?>
                                        <span class="badge bg-success-subtle text-success border border-success px-2 py-1 small">
                                            <i class="bi bi-eye-fill me-1"></i>Can View All Statistics
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border px-2 py-1 small">
                                            <i class="bi bi-eye-slash me-1"></i>Club Stats Only
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#changeRoleModal<?= htmlspecialchars($u['id']) ?>">
                                        <i class="bi bi-pencil-square me-1"></i> Change Role
                                    </button>

                                    <!-- Change Role Modal -->
                                    <div class="modal fade text-start" id="changeRoleModal<?= htmlspecialchars($u['id']) ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content rounded-4 border-0 shadow">
                                                <form action="<?= url('/admin/users/role') ?>" method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="user_id" value="<?= htmlspecialchars($u['id']) ?>">
                                                    <div class="modal-header border-bottom bg-light">
                                                        <h5 class="modal-title fw-bold">
                                                            <i class="bi bi-person-gear text-primary me-2"></i>Change User Role
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3 p-3 bg-light rounded-3 border">
                                                            <div class="fw-bold text-dark"><?= htmlspecialchars($u['name']) ?></div>
                                                            <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Select Assigned System Role</label>
                                                            <select name="role_id" class="form-select rounded-3 py-2 fw-semibold" required>
                                                                <?php foreach ($roles as $r): ?>
                                                                    <option value="<?= htmlspecialchars($r['id']) ?>" <?= ($u['role_id'] === $r['id'] || ($roleName === $r['name'])) ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($r['display_name']) ?> — <?= htmlspecialchars($r['description']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>

                                                        <div class="alert alert-info small mb-0 rounded-3">
                                                            <i class="bi bi-info-circle me-1"></i>
                                                            Users assigned to <strong>Super Admin</strong> or <strong>Platform Admin</strong> roles will have full permission to view all website statistics and platform management tools.
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-top bg-light">
                                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-bold rounded-3">Save Role Assignment</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Platform & Club Contact Messages -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
            <h5 class="fw-bold mb-3"><i class="bi bi-envelope-paper text-danger me-2"></i>Recent Platform & Club Contact Inquiries</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Recipient / Destination</th>
                            <th>Sender</th>
                            <th>Subject & Preview</th>
                            <th>Status</th>
                            <th>Received Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentMessages)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">No contact messages received yet</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentMessages as $m): ?>
                                <tr>
                                    <td>
                                        <?php if (empty($m['organization_id'])): ?>
                                            <span class="badge bg-dark text-white"><i class="bi bi-globe me-1"></i>Benchero Main Platform</span>
                                        <?php else: ?>
                                            <span class="badge bg-primary text-white"><i class="bi bi-shield me-1"></i><?= htmlspecialchars($m['org_name'] ?? 'Club') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark small"><?= htmlspecialchars($m['name']) ?></div>
                                        <a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="small text-decoration-none text-primary"><?= htmlspecialchars($m['email']) ?></a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark small"><?= htmlspecialchars($m['subject'] ?: '(No Subject)') ?></div>
                                        <div class="small text-muted text-truncate" style="max-width: 320px;"><?= htmlspecialchars(mb_strimwidth($m['message'], 0, 80, '...')) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($m['status'] === 'unread'): ?>
                                            <span class="badge bg-warning text-dark">Unread</span>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted border">Read</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= date('M j, Y H:i', strtotime($m['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Plan Pricing & Entitlement Configuration -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-5">
            <h5 class="fw-bold mb-3"><i class="bi bi-sliders me-2 text-primary"></i>Commercial Plan & Capability Configuration</h5>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Plan ID</th>
                            <th>Plan Name</th>
                            <th>Billing Interval</th>
                            <th>Price (KES)</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($plans as $plan): ?>
                            <tr>
                                <td><code>#<?= $plan['id'] ?></code></td>
                                <td class="fw-bold"><?= htmlspecialchars($plan['name']) ?></td>
                                <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($plan['billing_interval']) ?></span></td>
                                <td class="fw-bold">KES <?= number_format($plan['price_kes'], 2) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#editPlanModal<?= $plan['id'] ?>">
                                        <i class="bi bi-pencil me-1"></i> Edit Pricing
                                    </button>

                                    <!-- Edit Plan Modal -->
                                    <div class="modal fade text-start" id="editPlanModal<?= $plan['id'] ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content rounded-4 border-0">
                                                <form action="<?= url("/admin") ?>/plans/update" method="POST">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="plan_id" value="<?= $plan['id'] ?>">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">Edit Plan #<?= $plan['id'] ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Plan Name</label>
                                                            <input type="text" class="form-control" name="name" value="<?= htmlspecialchars($plan['name']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Price (KES)</label>
                                                            <input type="number" step="0.01" class="form-control" name="price_kes" value="<?= htmlspecialchars($plan['price_kes']) ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-primary fw-bold">Save Changes</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3">Recent Registered Organizations</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Club Name</th>
                                    <th>Slug</th>
                                    <th>Country</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrgs as $org): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($org['name']) ?></td>
                                        <td class="small text-muted"><?= htmlspecialchars($org['slug']) ?></td>
                                        <td><?= htmlspecialchars($org['country'] ?? 'KE') ?></td>
                                        <td class="small text-muted"><?= date('M j, Y', strtotime($org['created_at'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">Customer Custom Domains Management</h5>
                        <span class="badge bg-primary px-3 py-2"><?= count($domains) ?> Connected</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0 small">
                            <thead class="table-light">
                                <tr>
                                    <th>Organization</th>
                                    <th>Hostname</th>
                                    <th>Ownership TXT</th>
                                    <th>Routing</th>
                                    <th>SSL / TLS</th>
                                    <th>Created / Active</th>
                                    <th>Last Verification Diagnostic</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($domains)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No custom domains connected yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($domains as $dom): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold"><?= htmlspecialchars($dom['org_name'] ?? 'N/A') ?></div>
                                                <small class="text-muted">Slug: <code><?= htmlspecialchars($dom['org_slug'] ?? '') ?></code></small>
                                            </td>
                                            <td>
                                                <a href="<?= ($dom['ssl_status'] === 'active' ? 'https://' : 'http://') . htmlspecialchars($dom['domain']) ?>" target="_blank" class="fw-semibold text-primary text-decoration-none">
                                                    <?= htmlspecialchars($dom['domain']) ?> <i class="bi bi-box-arrow-up-right small"></i>
                                                </a>
                                                <?php if ($dom['domain'] !== $dom['normalized_domain']): ?>
                                                    <small class="text-muted d-block font-monospace">Norm: <?= htmlspecialchars($dom['normalized_domain']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($dom['verification_status'] === 'verified'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-check2"></i> VERIFIED</span>
                                                <?php elseif ($dom['verification_status'] === 'failed'): ?>
                                                    <span class="badge bg-danger"><i class="bi bi-x"></i> FAILED</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-clock"></i> PENDING</span>
                                                <?php endif; ?>
                                                <?php if (!empty($dom['verified_at'])): ?>
                                                    <small class="text-muted d-block" style="font-size:0.75rem;"><?= date('M j, Y', strtotime($dom['verified_at'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($dom['activation_status'] === 'active'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-power"></i> ACTIVE</span>
                                                <?php elseif ($dom['activation_status'] === 'disabled'): ?>
                                                    <span class="badge bg-secondary">DISABLED</span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning text-dark">PENDING</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($dom['ssl_status'] === 'active'): ?>
                                                    <span class="badge bg-success"><i class="bi bi-shield-check"></i> ACTIVE</span>
                                                <?php elseif ($dom['ssl_status'] === 'pending'): ?>
                                                    <span class="badge bg-warning text-dark"><i class="bi bi-shield-exclamation"></i> PENDING</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">NOT READY</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div><?= date('M j, Y', strtotime($dom['created_at'])) ?></div>
                                                <?php if (!empty($dom['activated_at'])): ?>
                                                    <small class="text-success" style="font-size:0.75rem;">Active: <?= date('M j, Y', strtotime($dom['activated_at'])) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td style="max-width:280px;">
                                                <?php if (!empty($dom['last_verification_error'])): ?>
                                                    <span class="text-danger small d-inline-block text-truncate" style="max-width:260px;" title="<?= htmlspecialchars($dom['last_verification_error']) ?>">
                                                        <i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($dom['last_verification_error']) ?>
                                                    </span>
                                                <?php elseif ($dom['verification_status'] === 'verified'): ?>
                                                    <span class="text-success small"><i class="bi bi-check-circle me-1"></i>TXT record verified</span>
                                                <?php else: ?>
                                                    <span class="text-muted small">No verification attempted yet</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
