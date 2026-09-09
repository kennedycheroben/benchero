<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-4 bg-dark text-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <span class="badge bg-danger px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Super Admin</span>
                <h2 class="fw-bold mb-0">Benchero Platform Administration</h2>
            </div>
            <span class="text-white-50">Logged in as <?= htmlspecialchars($user['name']) ?></span>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Organizations</div>
                    <div class="display-6 fw-extrabold text-primary"><?= number_format($stats['orgs']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Users</div>
                    <div class="display-6 fw-extrabold text-success"><?= number_format($stats['users']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Fixtures Scheduled</div>
                    <div class="display-6 fw-extrabold text-info"><?= number_format($stats['fixtures']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Completed Revenue</div>
                    <div class="display-6 fw-extrabold text-warning">KES <?= number_format($stats['revenue'], 2) ?></div>
                </div>
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
                                                <form action="/admin/plans/update" method="POST">
                                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
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

            <div class="col-lg-6">
                <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                    <h5 class="fw-bold mb-3">Recent Custom Domains</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Organization</th>
                                    <th>Domain</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($domains)): ?>
                                    <tr><td colspan="3" class="text-center text-muted py-3">No custom domains connected yet</td></tr>
                                <?php else: ?>
                                    <?php foreach ($domains as $dom): ?>
                                        <tr>
                                            <td class="fw-bold small"><?= htmlspecialchars($dom['org_name'] ?? 'N/A') ?></td>
                                            <td><code><?= htmlspecialchars($dom['domain']) ?></code></td>
                                            <td>
                                                <span class="badge bg-<?= $dom['status'] === 'active' ? 'success' : 'warning' ?>">
                                                    <?= htmlspecialchars(strtoupper($dom['status'])) ?>
                                                </span>
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
