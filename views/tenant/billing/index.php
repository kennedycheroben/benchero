<?php require __DIR__ . '/../../layouts/main.php'; ?>

<?php
    $sub = $subStatus ?? [
        'status' => 'EXPIRED',
        'is_visible' => false,
        'plan_name' => 'Free Trial',
        'price_kes' => 0,
        'billing_interval' => 'trial',
        'starts_at' => null,
        'expires_at' => null,
        'days_remaining' => 0
    ];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Billing & Subscriptions</h2>
        <p class="text-muted small">Manage your Benchero subscription plan, renewal, payment history, and M-Pesa billing.</p>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger mb-4 rounded-3">
        <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success mb-4 rounded-3">
        <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
    </div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Current Subscription Status</h5>
            
            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                <div>
                    <div class="small text-muted">Current Plan</div>
                    <div class="fw-bold fs-5"><?= htmlspecialchars($sub['plan_name']) ?></div>
                </div>
                <div>
                    <span class="badge bg-<?= $sub['status'] === 'ACTIVE' ? 'success' : ($sub['status'] === 'TRIAL' ? 'info' : 'danger') ?> px-3 py-2 text-uppercase fs-7">
                        <?= htmlspecialchars($sub['status']) ?>
                    </span>
                </div>
            </div>

            <div class="mb-3">
                <?php if ($sub['status'] === 'ACTIVE'): ?>
                    <div class="alert alert-success py-2 px-3 small rounded-3 mb-0 border-0">
                        <i class="bi bi-check-circle-fill me-1"></i> <strong>Active:</strong> Your public Benchero profile is visible.
                    </div>
                <?php elseif ($sub['status'] === 'TRIAL'): ?>
                    <div class="alert alert-info py-2 px-3 small rounded-3 mb-0 border-0">
                        <i class="bi bi-info-circle-fill me-1"></i> <strong>Free Trial:</strong> Your public profile is currently visible.
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger py-2 px-3 small rounded-3 mb-0 border-0">
                        <i class="bi bi-x-circle-fill me-1"></i> <strong>Expired:</strong> Your public profile is currently hidden.
                    </div>
                <?php endif; ?>
            </div>

            <ul class="list-unstyled text-muted small d-grid gap-2 mb-4">
                <li><strong>Price:</strong> KSh <?= number_format($sub['price_kes']) ?></li>
                <li><strong>Billing Interval:</strong> <?= htmlspecialchars(ucfirst($sub['billing_interval'])) ?></li>
                <li><strong>Started:</strong> <?= !empty($sub['starts_at']) ? date('M j, Y', strtotime($sub['starts_at'])) : 'N/A' ?></li>
                <li><strong>Expires:</strong> <?= !empty($sub['expires_at']) ? date('M j, Y H:i', strtotime($sub['expires_at'])) : 'N/A' ?></li>
                <li><strong>Days Remaining:</strong> <span class="fw-bold <?= $sub['days_remaining'] <= 3 ? 'text-danger' : 'text-dark' ?>"><?= (int)$sub['days_remaining'] ?> days</span></li>
            </ul>

            <button type="button" class="btn btn-primary fw-bold rounded-3 py-2.5 shadow-sm mt-auto" data-bs-toggle="modal" data-bs-target="#mpesaModal">
                <i class="bi bi-phone me-1"></i> <?= $sub['status'] === 'EXPIRED' ? 'Renew Subscription via M-Pesa' : 'Upgrade / Renew via M-Pesa' ?>
            </button>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
            <h5 class="fw-bold mb-3"><i class="bi bi-credit-card text-success me-2"></i>Payment History</h5>
            <?php if (empty($payments)): ?>
                <div class="text-center py-5 my-auto text-muted small">
                    <i class="bi bi-receipt display-5 d-block mb-2 text-secondary opacity-50"></i>
                    No payment transactions recorded yet.
                </div>
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
                                    <td class="fw-bold">KSh <?= number_format($pay['amount'], 2) ?></td>
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
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content rounded-4 border-0 p-3">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold"><i class="bi bi-phone text-success me-2"></i>M-Pesa Subscription Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="stkAlert" class="alert d-none rounded-3 mb-3"></div>

                <div id="stkStatusContainer" class="d-none text-center py-4">
                    <div class="spinner-border text-success mb-3" style="width: 3rem; height: 3rem;" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5 class="fw-bold mb-2 text-dark" id="stkStatusTitle">M-Pesa Payment Request Sent</h5>
                    <p class="text-muted small mb-3" id="stkStatusMsg">
                        Check your phone. Enter your M-Pesa PIN on the prompt to complete the payment.
                    </p>
                    <div class="badge bg-warning-subtle text-warning border border-warning px-3 py-2 text-uppercase fs-7 rounded-pill mb-3" id="stkStatusBadge">
                        Waiting for confirmation...
                    </div>
                    <p class="text-muted fs-8 mb-0">Do not close this window while payment is processing.</p>
                </div>

                <form id="stkForm" action="<?= url('/o/' . urlencode($tenant['slug']) . '/billing/stkpush') ?>" method="POST">
                    <?= csrf_field() ?>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Commercial Subscription Plan</label>
                        <div class="d-grid gap-3">
                            <?php foreach ($plans as $p): ?>
                                <?php if (($p['price_kes'] ?? 0) > 0): ?>
                                    <?php 
                                        $isPro = (str_contains(strtolower($p['name']), 'pro') || $p['id'] == 4);
                                    ?>
                                    <div class="form-check card p-3 border rounded-3 position-relative <?= $isPro ? 'border-primary bg-primary bg-opacity-10' : '' ?>">
                                        <input class="form-check-input mt-1" type="radio" name="plan_id" id="plan_<?= $p['id'] ?>" value="<?= $p['id'] ?>" <?= $isPro ? 'checked' : '' ?> required>
                                        <label class="form-check-label w-100 cursor-pointer" for="plan_<?= $p['id'] ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <div>
                                                    <strong class="d-block text-dark fs-5"><?= htmlspecialchars($p['name']) ?></strong>
                                                    <span class="text-muted small">KSh <?= number_format($p['price_kes']) ?> / <?= htmlspecialchars($p['billing_interval']) ?></span>
                                                </div>
                                                <?php if ($isPro): ?>
                                                    <span class="badge bg-primary px-3 py-1 fw-bold">
                                                        <i class="bi bi-star-fill me-1"></i>BENCHERO PRO
                                                    </span>
                                                <?php elseif ($p['billing_interval'] === 'yearly'): ?>
                                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1 small">
                                                        Save KSh 2,000
                                                    </span>
                                                <?php endif; ?>
                                            </div>

                                            <?php if ($isPro): ?>
                                                <p class="small text-muted mb-0 mt-1">
                                                    <strong>Pro Features:</strong> Custom Domain (<code>www.myclub.co.ke</code>), Controlled Video Uploads (2 GB), Club Media Center (5 GB), Digital Club Card & QR Codes, Data Exports (CSV/JSON), Advanced Stats & Standings, Custom OG Images & Social Sharing, Additional Admins, Priority Support, and Removal of Benchero Branding.
                                                </p>
                                            <?php endif; ?>
                                        </label>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="phone_number" class="form-label fw-semibold">Safaricom M-Pesa Phone Number</label>
                        <input type="text" id="phone_number" name="phone_number" class="form-control form-control-lg" placeholder="e.g. 0712345678" required>
                        <div class="form-text">An STK Push prompt will be sent directly to your phone to authorize payment.</div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4">
                        <button type="button" class="btn btn-outline-secondary rounded-3" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="paySubmitBtn" class="btn btn-success fw-bold px-4 rounded-3">
                            <i class="bi bi-shield-check me-1"></i>Pay & Activate Now
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const stkForm = document.getElementById('stkForm');
    const paySubmitBtn = document.getElementById('paySubmitBtn');
    const stkAlert = document.getElementById('stkAlert');
    const stkStatusContainer = document.getElementById('stkStatusContainer');
    const stkStatusTitle = document.getElementById('stkStatusTitle');
    const stkStatusMsg = document.getElementById('stkStatusMsg');
    const stkStatusBadge = document.getElementById('stkStatusBadge');
    
    let pollInterval = null;
    let pollCount = 0;
    const maxPolls = 30; // 30 * 3s = 90 seconds max

    if (!stkForm) return;

    stkForm.addEventListener('submit', function (e) {
        e.preventDefault();
        
        const phone = document.getElementById('phone_number').value.trim();
        const planRadio = stkForm.querySelector('input[name="plan_id"]:checked');
        const planId = planRadio ? planRadio.value : null;

        if (!phone || !planId) {
            showAlert('danger', 'Please provide a valid phone number and select a plan.');
            return;
        }

        paySubmitBtn.disabled = true;
        paySubmitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Initiating Payment...';

        // Step 1: Create payment intent via AJAX
        const formData = new FormData();
        formData.append('phone_number', phone);
        formData.append('plan_id', planId);

        fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent') ?>', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                paySubmitBtn.disabled = false;
                paySubmitBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay & Activate Now';
                showAlert('danger', data.error || 'Failed to create payment request.');
                return;
            }

            const intentId = data.intent_id;

            // Step 2: Initiate STK push
            fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent/') ?>' + intentId + '/initiate', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(initRes => {
                stkForm.classList.add('d-none');
                stkStatusContainer.classList.remove('d-none');

                if (initRes.status === 'completed_mock' || initRes.status === 'completed') {
                    showSuccessState('Payment Verified! Subscription Active.');
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                // Step 3: Begin status polling
                startPolling(intentId);
            })
            .catch(err => {
                resetForm();
                showAlert('danger', 'Network error while sending STK push request.');
            });
        })
        .catch(err => {
            paySubmitBtn.disabled = false;
            paySubmitBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay & Activate Now';
            showAlert('danger', 'An error occurred. Please check your phone number and try again.');
        });
    });

    function startPolling(intentId) {
        pollCount = 0;
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(function () {
            pollCount++;
            if (pollCount > maxPolls) {
                clearInterval(pollInterval);
                showErrorState('Payment confirmation timed out. If you completed payment, your account will update automatically within 1 minute.');
                return;
            }

            fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent/') ?>' + intentId + '/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    showSuccessState('Payment Verified! Your Benchero subscription is now active.');
                    setTimeout(() => window.location.reload(), 1800);
                } else if (data.status === 'failed' || data.status === 'cancelled' || data.status === 'expired') {
                    clearInterval(pollInterval);
                    const msg = data.status === 'cancelled' ? 'Payment was cancelled.' : (data.result_desc || 'Payment could not be completed.');
                    showErrorState(msg);
                }
            })
            .catch(() => {});
        }, 3000);
    }

    function showAlert(type, msg) {
        stkAlert.className = `alert alert-${type} rounded-3 mb-3`;
        stkAlert.textContent = msg;
        stkAlert.classList.remove('d-none');
    }

    function showSuccessState(msg) {
        stkStatusTitle.textContent = '✓ Payment Successful';
        stkStatusMsg.textContent = msg;
        stkStatusBadge.className = 'badge bg-success px-3 py-2 text-uppercase fs-7 rounded-pill mb-3';
        stkStatusBadge.textContent = 'Active Subscription';
    }

    function showErrorState(msg) {
        stkStatusTitle.textContent = 'Payment Unsuccessful';
        stkStatusMsg.textContent = msg;
        stkStatusBadge.className = 'badge bg-danger px-3 py-2 text-uppercase fs-7 rounded-pill mb-3';
        stkStatusBadge.textContent = 'Failed';

        setTimeout(() => {
            resetForm();
            showAlert('danger', msg);
        }, 3000);
    }

    function resetForm() {
        stkForm.classList.remove('d-none');
        stkStatusContainer.classList.add('d-none');
        paySubmitBtn.disabled = false;
        paySubmitBtn.innerHTML = '<i class="bi bi-shield-check me-1"></i>Pay & Activate Now';
    }
});
</script>
