<?php require __DIR__ . '/../layouts/main.php'; ?>

<?php
    $statusInfo = $subscriptionStatus ?? [
        'status' => 'EXPIRED',
        'is_visible' => false,
        'warning_message' => null,
        'days_remaining' => 0,
        'plan_name' => 'None',
        'price_kes' => 0
    ];
?>

<div class="container mt-4">
    <!-- Subscription Warning & Expiry Banners -->
    <?php if ($statusInfo['status'] === 'EXPIRED'): ?>
        <div class="alert alert-danger shadow-sm rounded-4 p-4 mb-4 border-0 border-start border-danger border-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Subscription Expired</h5>
                    <p class="mb-0 text-dark">
                        Your subscription has expired. Your public club profile is currently hidden. Renew your subscription to make your profile visible again.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="btn btn-danger fw-bold rounded-3 text-nowrap">
                        <i class="bi bi-credit-card me-1"></i>Renew Subscription
                    </a>
                    <a href="<?= url('/pricing') ?>" class="btn btn-outline-secondary fw-bold rounded-3 text-nowrap">
                        View Plans
                    </a>
                </div>
            </div>
        </div>
    <?php elseif (!empty($statusInfo['warning_message'])): ?>
        <div class="alert alert-warning shadow-sm rounded-4 p-4 mb-4 border-0 border-start border-warning border-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-clock-history me-2"></i>Subscription Expiring Soon</h5>
                    <p class="mb-0 text-dark"><?= htmlspecialchars($statusInfo['warning_message']) ?></p>
                </div>
                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="btn btn-warning fw-bold rounded-3 text-nowrap">
                    <i class="bi bi-arrow-repeat me-1"></i>Renew Subscription
                </a>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Sidebar Navigation -->
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
                <div class="card-body p-4 bg-primary text-white">
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($tenant['name']) ?></h5>
                    <p class="small text-white-50 mb-0">Role: <?= htmlspecialchars(ucfirst($role)) ?></p>
                </div>
                <div class="list-group list-group-flush">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/dashboard" class="list-group-item list-group-item-action active fw-semibold"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/sports" class="list-group-item list-group-item-action"><i class="bi bi-trophy me-2"></i>Manage Sports</a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="list-group-item list-group-item-action"><i class="bi bi-receipt me-2"></i>Billing & Plans</a>
                    <a href="/club/<?= htmlspecialchars($tenant['slug']) ?>" target="_blank" class="list-group-item list-group-item-action"><i class="bi bi-globe me-2"></i>Public Club Profile <i class="bi bi-box-arrow-up-right small ms-1"></i></a>
                </div>
            </div>
            
            <form method="POST" action="/logout">
                <?= csrf_field() ?>
                <button class="btn btn-outline-danger w-100 rounded-3 py-2 fw-semibold"><i class="bi bi-box-arrow-right me-1"></i>Log Out</button>
            </form>
        </div>

        <!-- Main Dashboard Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h2 class="fw-bold mb-0">Club Dashboard</h2>
                    <p class="text-muted small">Welcome back to <?= htmlspecialchars($tenant['name']) ?> management panel.</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Subscription Status</h5>
                        
                        <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                            <div>
                                <div class="small text-muted">Current Plan</div>
                                <div class="fw-bold fs-5"><?= htmlspecialchars($statusInfo['plan_name'] ?? 'Free Trial') ?></div>
                            </div>
                            <div>
                                <span class="badge bg-<?= $statusInfo['status'] === 'ACTIVE' ? 'success' : ($statusInfo['status'] === 'TRIAL' ? 'info' : 'danger') ?> px-3 py-2 fs-7 text-uppercase">
                                    <?= htmlspecialchars($statusInfo['status']) ?>
                                </span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <?php if ($statusInfo['status'] === 'ACTIVE'): ?>
                                <div class="text-success small fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i>Active. Your public Benchero profile is visible.
                                </div>
                            <?php elseif ($statusInfo['status'] === 'TRIAL'): ?>
                                <div class="text-info small fw-semibold">
                                    <i class="bi bi-info-circle-fill me-1"></i>Free Trial. Your public profile is currently visible.
                                </div>
                            <?php else: ?>
                                <div class="text-danger small fw-semibold">
                                    <i class="bi bi-x-circle-fill me-1"></i>Expired. Your public profile is currently hidden.
                                </div>
                            <?php endif; ?>
                        </div>

                        <ul class="list-unstyled text-muted small d-grid gap-2 mb-4">
                            <li><strong>Billing Interval:</strong> <?= htmlspecialchars(ucfirst($statusInfo['billing_interval'])) ?></li>
                            <li><strong>Price:</strong> KSh <?= number_format($statusInfo['price_kes']) ?></li>
                            <?php if (!empty($statusInfo['expires_at'])): ?>
                                <li><strong>Expires:</strong> <?= date('F j, Y', strtotime($statusInfo['expires_at'])) ?></li>
                                <li><strong>Days Remaining:</strong> <?= (int)$statusInfo['days_remaining'] ?> days</li>
                            <?php endif; ?>
                        </ul>

                        <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="btn btn-primary fw-bold rounded-3 w-100 py-2">
                            <i class="bi bi-gear me-1"></i>Manage Billing & Subscription
                        </a>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold mb-3"><i class="bi bi-building text-secondary me-2"></i>Organization Information</h5>
                        <ul class="list-unstyled text-muted small d-grid gap-3 my-auto">
                            <li class="d-flex justify-content-between border-bottom pb-2">
                                <strong>Club Slug:</strong> <span><?= htmlspecialchars($tenant['slug']) ?></span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom pb-2">
                                <strong>Country:</strong> <span><?= htmlspecialchars($tenant['country']) ?></span>
                            </li>
                            <li class="d-flex justify-content-between border-bottom pb-2">
                                <strong>Timezone:</strong> <span><?= htmlspecialchars($tenant['timezone']) ?></span>
                            </li>
                            <li class="d-flex justify-content-between">
                                <strong>Public Profile Link:</strong>
                                <a href="<?= url('/club/' . urlencode($tenant['slug'])) ?>" target="_blank" class="fw-semibold">View Club Page <i class="bi bi-box-arrow-up-right small"></i></a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
