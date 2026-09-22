<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-4 bg-dark text-white border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <span class="badge bg-danger px-3 py-1 rounded-pill text-uppercase fw-bold mb-1">Super Admin</span>
                <h2 class="fw-bold mb-0"><i class="bi bi-credit-card-2-front me-2"></i>Payments & Reconciliation Dashboard</h2>
            </div>
            <div>
                <a href="<?= url('/admin') ?>" class="btn btn-outline-light btn-sm fw-bold"><i class="bi bi-arrow-left me-1"></i>Back to Admin</a>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <?php if (!empty($success)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Total Revenue</div>
                    <div class="fs-3 fw-bold text-success">KES <?= number_format($stats['revenue'], 2) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Successful Payments</div>
                    <div class="fs-3 fw-bold text-primary"><?= number_format($stats['successful']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Pending Requests</div>
                    <div class="fs-3 fw-bold text-warning"><?= number_format($stats['pending']) ?></div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="p-4 bg-white rounded-4 shadow-sm border">
                    <div class="text-muted small fw-bold text-uppercase mb-1">Failed / Cancelled</div>
                    <div class="fs-3 fw-bold text-danger"><?= number_format($stats['failed']) ?></div>
                </div>
            </div>
        </div>

        <!-- Filter Tabs -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h5 class="fw-bold mb-0"><i class="bi bi-funnel me-2 text-primary"></i>Filter Payment Transactions</h5>
                <button type="button" class="btn btn-outline-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#manualReconcileModal">
                    <i class="bi bi-tools me-1"></i>Manual Reconciliation
                </button>
            </div>

            <ul class="nav nav-pills gap-2 mb-3">
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'all' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=all') ?>">All Transactions</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'pending' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=pending') ?>">Pending / Initiated</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'completed' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=completed') ?>">Successful</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'failed' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=failed') ?>">Failed</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'cancelled' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=cancelled') ?>">Cancelled</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link fw-bold <?= $currentFilter === 'expired' ? 'active' : 'bg-light text-dark' ?>" href="<?= url('/admin/payments?status=expired') ?>">Expired</a>
                </li>
            </ul>

            <!-- Transactions Table -->
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Created</th>
                            <th>Organization</th>
                            <th>Customer</th>
                            <th>Plan</th>
                            <th>Amount</th>
                            <th>Provider & Method</th>
                            <th>Payer / Phone</th>
                            <th>Benchero Ref</th>
                            <th>Receipt / Ref</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($intents)): ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-5">
                                    <i class="bi bi-inbox display-6 d-block mb-2 text-secondary opacity-50"></i>
                                    No payment transactions found matching filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($intents as $item): ?>
                                <tr>
                                    <td class="small text-muted"><?= date('M j, Y H:i', strtotime($item['created_at'])) ?></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['org_name'] ?? 'N/A') ?></td>
                                    <td class="small"><?= htmlspecialchars($item['user_name'] ?? $item['user_email'] ?? 'N/A') ?></td>
                                    <td class="fw-semibold small"><?= htmlspecialchars($item['plan_name'] ?? 'N/A') ?></td>
                                    <td class="fw-bold text-dark"><?= htmlspecialchars($item['currency'] ?? 'KES') ?> <?= number_format($item['amount'], 2) ?></td>
                                    <td>
                                        <?php if (($item['provider'] ?? 'imbank') === 'paypal'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 small">
                                                <i class="bi bi-paypal me-1"></i>PayPal (<?= htmlspecialchars(ucfirst($item['payment_method'] ?? 'paypal')) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                <i class="bi bi-phone me-1"></i>I&M (M-Pesa)
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="font-monospace small text-muted"><?= htmlspecialchars($item['masked_phone']) ?></td>
                                    <td><code class="small text-primary"><?= htmlspecialchars($item['benchero_reference']) ?></code></td>
                                    <td>
                                        <code class="small text-success fw-bold"><?= htmlspecialchars($item['mpesa_receipt_number'] ?: ($item['provider_reference'] ?: '-')) ?></code>
                                    </td>
                                    <td>
                                        <?php 
                                            $st = strtolower($item['status']);
                                            $badgeClass = match($st) {
                                                'completed' => 'bg-success',
                                                'initiated', 'pending' => 'bg-warning text-dark',
                                                'failed' => 'bg-danger',
                                                'cancelled' => 'bg-secondary',
                                                'expired' => 'bg-dark',
                                                default => 'bg-secondary'
                                            };
                                        ?>
                                        <span class="badge <?= $badgeClass ?> px-2.5 py-1 rounded-pill text-uppercase">
                                            <?= htmlspecialchars($st === 'completed' ? 'Successful' : $st) ?>
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
</section>

<!-- Manual Reconciliation Modal -->
<div class="modal fade" id="manualReconcileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-4 border-0">
            <form action="<?= url('/admin/payments/reconcile') ?>" method="POST">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($_SESSION['_csrf'] ?? '') ?>">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tools text-warning me-2"></i>Manual Administrative Reconciliation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="small text-muted mb-3">Use this recovery tool only for exceptional cases where an official notification was delayed or missed.</p>
                    
                    <div class="mb-3">
                        <label for="intent_id" class="form-label fw-semibold">Benchero Payment Intent ID / Reference</label>
                        <input type="text" id="intent_id" name="intent_id" class="form-control" placeholder="e.g. BENCH-PI-XXXXXX" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="receipt_number" class="form-label fw-semibold">Verified Provider Receipt Number</label>
                        <input type="text" id="receipt_number" name="receipt_number" class="form-control font-monospace" placeholder="e.g. QKH7890XYZ" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning fw-bold rounded-3"><i class="bi bi-check-circle me-1"></i>Reconcile & Activate</button>
                </div>
            </form>
        </div>
    </div>
</div>
