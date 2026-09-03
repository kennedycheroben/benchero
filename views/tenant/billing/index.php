<?php require __DIR__ . '/../../layouts/main.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Billing & Subscriptions</h2>
        <p class="text-muted small">Manage your Benchero subscription plan, payment history, and M-Pesa billing.</p>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger mb-4">
        <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success mb-4">
        <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Current Subscription Status</h5>
            
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                <div>
                    <div class="small text-muted">Active Plan</div>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($subscription['plan_name'] ?? 'Free Trial') ?></div>
                </div>
                <div>
                    <span class="badge bg-success px-3 py-2 text-uppercase fs-7">
                        <?= htmlspecialchars(ucfirst($subscription['status'] ?? 'trialing')) ?>
                    </span>
                </div>
            </div>

            <ul class="list-unstyled text-muted small d-grid gap-2 mb-4">
                <li><strong>Billing Interval:</strong> <?= htmlspecialchars(ucfirst($subscription['billing_interval'] ?? 'monthly')) ?></li>
                <li><strong>Trial / Current Period End:</strong> <?= !empty($subscription['trial_ends_at']) ? date('M j, Y H:i', strtotime($subscription['trial_ends_at'])) : (empty($subscription['current_period_end']) ? 'Active' : date('M j, Y', strtotime($subscription['current_period_end']))) ?></li>
            </ul>

            <button type="button" class="btn btn-primary fw-bold rounded-3" data-bs-toggle="modal" data-bs-target="#mpesaModal">
                <i class="bi bi-phone me-1"></i> Pay via M-Pesa Express
            </button>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-credit-card text-success me-2"></i>Payment History</h5>
            <?php if (empty($payments)): ?>
                <p class="text-muted small my-auto text-center py-4">No payment transactions recorded yet.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>M-Pesa Receipt</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="small"><?= date('M j, Y', strtotime($pay['created_at'])) ?></td>
                                    <td class="fw-bold">KES <?= number_format($pay['amount'], 2) ?></td>
                                    <td class="small text-monospace"><?= htmlspecialchars($pay['mpesa_receipt_number'] ?: 'Pending') ?></td>
                                    <td>
                                        <span class="badge bg-<?= $pay['status'] === 'completed' ? 'success' : ($pay['status'] === 'pending' ? 'warning' : 'danger') ?> fs-8">
                                            <?= htmlspecialchars(ucfirst($pay['status'])) ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- M-Pesa Modal -->
<div class="modal fade" id="mpesaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-phone text-success me-2"></i>M-Pesa Express Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="<?= url('/o/' . urlencode($tenant['slug']) . '/billing/stkpush') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="plan_id" class="form-label fw-semibold">Select Subscription Plan</label>
                        <select id="plan_id" name="plan_id" class="form-select" required>
                            <?php foreach ($plans as $p): ?>
                                <option value="<?= $p['id'] ?>">
                                    <?= htmlspecialchars($p['name']) ?> — KES <?= number_format($p['price_kes']) ?>/mo
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="phone_number" class="form-label fw-semibold">Safaricom M-Pesa Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-control form-control-lg" placeholder="0712345678 or 254712345678" required>
                        <div class="form-text">An STK Push prompt will be sent directly to your phone.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success fw-bold px-4">Pay Now via M-Pesa</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
