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
                    <h5 class="fw-bold mb-3">Recent M-Pesa & Plan Payments</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Organization</th>
                                    <th>Amount</th>
                                    <th>Receipt</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentPayments as $pay): ?>
                                    <tr>
                                        <td class="fw-bold small"><?= htmlspecialchars($pay['org_name'] ?? 'N/A') ?></td>
                                        <td class="fw-bold">KES <?= number_format($pay['amount'], 2) ?></td>
                                        <td class="small text-monospace"><?= htmlspecialchars($pay['mpesa_receipt_number'] ?: 'Pending') ?></td>
                                        <td>
                                            <span class="badge bg-<?= $pay['status'] === 'completed' ? 'success' : 'warning' ?>">
                                                <?= htmlspecialchars(ucfirst($pay['status'])) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
