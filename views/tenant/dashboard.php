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
    $s = $stats ?? ['teams' => 0, 'players' => 0, 'staff' => 0, 'fixtures' => 0];
?>

<div class="container-fluid py-3">
    <!-- Subscription Expiry & Warning Banners -->
    <?php if ($statusInfo['status'] === 'EXPIRED'): ?>
        <div class="alert alert-danger shadow-sm rounded-4 p-4 mb-4 border-0 border-start border-danger border-5">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                <div>
                    <h5 class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Subscription Expired</h5>
                    <p class="mb-0 text-dark">
                        Your Benchero subscription has expired. Your public club website is temporarily locked. Renew to publish your profile again.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="btn btn-danger fw-bold rounded-3 text-nowrap">
                        <i class="bi bi-credit-card me-1"></i>Renew Subscription
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

    <!-- Website Status & Readiness Card -->
    <?php if (!empty($websiteReadiness)): ?>
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4 border-start border-4 border-<?= $websiteReadiness['score'] >= 80 ? 'success' : 'primary' ?>">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-4">
                    <div class="text-center bg-light p-3 rounded-4 border" style="min-width: 120px;">
                        <span class="text-muted small fw-bold text-uppercase d-block mb-1">Website Readiness</span>
                        <div class="display-6 fw-black text-<?= $websiteReadiness['score'] >= 80 ? 'success' : ($websiteReadiness['score'] >= 50 ? 'warning' : 'danger') ?>">
                            <?= $websiteReadiness['score'] ?>%
                        </div>
                    </div>
                    <div>
                        <h4 class="fw-bold mb-1"><i class="bi bi-globe2 text-primary me-2"></i>Official Club Website Status</h4>
                        <p class="text-muted mb-0">Your public site is live at <code>https://benchero.co.ke/club/<?= htmlspecialchars($tenant['slug']) ?></code>.</p>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website/customize" class="btn btn-primary fw-bold rounded-3">
                        <i class="bi bi-magic me-1"></i> Customize Website
                    </a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/website" class="btn btn-outline-dark fw-semibold rounded-3">
                        <i class="bi bi-sliders me-1"></i> Readiness Overview
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Quick Stats Metric Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Teams</div>
                        <div class="display-6 fw-bold text-dark mt-1"><?= (int)$s['teams'] ?></div>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-3">
                        <i class="bi bi-people fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Players</div>
                        <div class="display-6 fw-bold text-dark mt-1"><?= (int)$s['players'] ?></div>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-3">
                        <i class="bi bi-person-badge fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Staff & Coaches</div>
                        <div class="display-6 fw-bold text-dark mt-1"><?= (int)$s['staff'] ?></div>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-3">
                        <i class="bi bi-person-vcard fs-3"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-white border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-muted small fw-semibold text-uppercase">Matches Scheduled</div>
                        <div class="display-6 fw-bold text-dark mt-1"><?= (int)$s['fixtures'] ?></div>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-3">
                        <i class="bi bi-calendar-event fs-3"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Subscription & Quick Actions -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
                <h5 class="fw-bold mb-3"><i class="bi bi-shield-check text-primary me-2"></i>Subscription Plan</h5>
                
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

                <ul class="list-unstyled text-muted small d-grid gap-2 mb-3">
                    <li><strong>Interval:</strong> <?= htmlspecialchars(ucfirst($statusInfo['billing_interval'])) ?></li>
                    <li><strong>Price:</strong> KSh <?= number_format($statusInfo['price_kes']) ?></li>
                    <?php if (!empty($statusInfo['expires_at'])): ?>
                        <li><strong>Expires:</strong> <?= date('F j, Y', strtotime($statusInfo['expires_at'])) ?> (<?= (int)$statusInfo['days_remaining'] ?> days remaining)</li>
                    <?php endif; ?>
                </ul>

                <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/billing" class="btn btn-outline-primary fw-semibold rounded-3 w-100 py-2">
                    <i class="bi bi-credit-card me-1"></i>Manage Subscription & Payments
                </a>
            </div>

            <!-- Quick Management Links -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
                <h5 class="fw-bold mb-3"><i class="bi bi-lightning-charge text-warning me-2"></i>Quick Tasks</h5>
                <div class="d-grid gap-2">
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/profile" class="btn btn-light text-start p-3 rounded-3 fw-semibold text-dark">
                        <i class="bi bi-sliders me-2 text-primary"></i> Edit Club Profile & Logo
                    </a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/staff" class="btn btn-light text-start p-3 rounded-3 fw-semibold text-dark">
                        <i class="bi bi-person-plus me-2 text-info"></i> Manage Coaches & Staff
                    </a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/content" class="btn btn-light text-start p-3 rounded-3 fw-semibold text-dark">
                        <i class="bi bi-newspaper me-2 text-success"></i> Post News & Matchday Photos
                    </a>
                    <a href="/o/<?= htmlspecialchars($tenant['slug']) ?>/sports" class="btn btn-light text-start p-3 rounded-3 fw-semibold text-dark">
                        <i class="bi bi-trophy me-2 text-warning"></i> Active Sports Setup
                    </a>
                </div>
            </div>
        </div>

        <!-- Recent Matches & Fixture Status -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                <div class="card-header bg-white border-bottom p-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0"><i class="bi bi-calendar3 text-primary me-2"></i>Recent & Upcoming Matches</h5>
                    <span class="badge bg-light text-dark border">Schedule Overview</span>
                </div>

                <?php if (empty($recentFixtures)): ?>
                    <div class="p-5 text-center text-muted">
                        <i class="bi bi-calendar-x display-4 mb-2 d-block"></i>
                        <p class="mb-0">No matches created yet for this organization.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Match Date</th>
                                    <th>Matchup</th>
                                    <th class="text-center">Score / Status</th>
                                    <th>Venue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentFixtures as $f): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold text-dark"><?= date('M j, Y H:i', strtotime($f['scheduled_at'])) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($f['sport_name']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($f['home_team_name']) ?> <span class="text-muted">vs</span> <?= htmlspecialchars($f['away_team_name']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php if ($f['status'] === 'completed' && $f['home_score'] !== null): ?>
                                                <span class="badge bg-dark fs-6 px-3 py-1">
                                                    <?= (int)$f['home_score'] ?> - <?= (int)$f['away_score'] ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary px-3 py-1 fw-semibold">
                                                    <?= htmlspecialchars(ucfirst($f['status'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small text-muted"><?= htmlspecialchars($f['venue_name'] ?: 'TBD') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
