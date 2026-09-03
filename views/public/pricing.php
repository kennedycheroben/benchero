<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-white border-bottom text-center">
    <div class="container py-lg-4">
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3">Benchero Plans & Pricing</span>
        <h1 class="display-5 fw-extrabold mb-3">Simple & Transparent Pricing for Every Club</h1>
        <p class="lead text-muted max-w-2xl mx-auto mb-4">Empower your sports organization with complete team, player, fixture and public page management.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 justify-content-center align-items-stretch">
            <?php foreach ($plans as $plan): ?>
                <?php 
                    $features = json_decode($plan['features'] ?? '{}', true);
                    $price = (float)($plan['price_kes'] ?? 0);
                    $interval = $plan['billing_interval'] ?? 'monthly';
                    $isYearly = ($interval === 'yearly');
                    $isTrial = ($interval === 'trial' || $price == 0);
                ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 position-relative <?= $isYearly ? 'border border-primary border-2 shadow' : '' ?>">
                        <?php if ($isYearly): ?>
                            <span class="badge bg-success text-white position-absolute top-0 end-0 m-3 px-3 py-2 rounded-pill fw-bold">
                                <i class="bi bi-piggy-bank me-1"></i>SAVE KSh 2,000
                            </span>
                        <?php elseif ($isTrial): ?>
                            <span class="badge bg-secondary text-white position-absolute top-0 end-0 m-3 px-3 py-1 rounded-pill">
                                14-Day Free Access
                            </span>
                        <?php endif; ?>
                        
                        <h4 class="fw-bold mb-1 mt-2"><?= htmlspecialchars($plan['name']) ?></h4>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($features['description'] ?? '') ?></p>

                        <div class="my-3">
                            <span class="display-6 fw-extrabold text-dark">KSh <?= number_format($price) ?></span>
                            <span class="text-muted small">
                                <?php if ($isTrial): ?>
                                    / trial
                                <?php elseif ($isYearly): ?>
                                    / year
                                <?php else: ?>
                                    / month
                                <?php endif; ?>
                            </span>
                        </div>

                        <?php if ($isYearly): ?>
                            <div class="alert alert-success-subtle py-2 px-3 rounded-3 small mb-3 text-success border border-success-subtle">
                                <i class="bi bi-check-circle-fill me-1"></i> Pay <strong>KSh 10,000/year</strong> instead of KSh 12,000 monthly!
                            </div>
                        <?php elseif (!$isTrial): ?>
                            <div class="text-muted small mb-3">
                                KSh 12,000/year if paid monthly
                            </div>
                        <?php else: ?>
                            <div class="text-muted small mb-3">
                                Full public profile access included
                            </div>
                        <?php endif; ?>
                        
                        <ul class="list-unstyled d-grid gap-2 mb-4 text-secondary small">
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $features['teams_limit'] ?? 10 ?> Teams Limit</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $features['player_limits'] ?? 100 ?> Player Roster Capacity</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Multi-Sport Operations</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Fixtures & Results Tracking</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Public Club Profile Page</li>
                        </ul>
                        
                        <a href="<?= url('/register?plan=' . urlencode($plan['slug'])) ?>" class="btn <?= $isYearly ? 'btn-primary' : ($isTrial ? 'btn-outline-primary' : 'btn-dark') ?> w-100 fw-bold py-2.5 rounded-3 mt-auto shadow-sm">
                            <?php if ($isTrial): ?>
                                Start Free Trial
                            <?php elseif ($isYearly): ?>
                                Choose Yearly
                            <?php else: ?>
                                Choose Monthly
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
