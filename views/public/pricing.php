<?php $this->layout('layout', ['title' => $title]) ?>

<style>
.pricing-card {
    height: auto !important;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    overflow-wrap: anywhere;
    word-break: normal;
}

.pricing-card:hover {
    transform: translateY(-2px);
}

.pricing-badge {
    position: static !important;
    display: inline-flex;
    align-items: center;
    max-width: 100%;
    white-space: normal;
    word-break: break-word;
}

.pricing-features-extra {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease-in-out;
}

.pricing-features-extra.is-expanded {
    max-height: 500px;
    opacity: 1;
}

.features-toggle-btn {
    font-size: 0.85rem;
    cursor: pointer;
    box-shadow: none !important;
}

.features-toggle-btn:focus-visible {
    outline: 2px solid var(--bs-primary);
    outline-offset: 2px;
    border-radius: 4px;
}

.features-toggle-btn .toggle-icon {
    transition: transform 0.25s ease;
}

.features-toggle-btn[aria-expanded="true"] .toggle-icon {
    transform: rotate(180deg);
}
</style>

<section class="py-5 bg-white border-bottom text-center">
    <div class="container py-lg-4">
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3">Benchero Commercial Plans</span>
        <h1 class="display-5 fw-extrabold mb-3">Simple & Transparent Pricing for Every Club</h1>
        <p class="lead text-muted max-w-2xl mx-auto mb-4">Empower your sports organization with complete team, player, fixture, media and public digital presence.</p>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 justify-content-center align-items-start">
            <?php foreach ($plans as $plan): ?>
                <?php 
                    $features = json_decode($plan['features'] ?? '{}', true);
                    $price = (float)($plan['price_kes'] ?? 0);
                    $interval = $plan['billing_interval'] ?? 'monthly';
                    $isPro = (str_contains(strtolower($plan['name']), 'pro') || $plan['id'] == 4);
                    $isYearly = ($interval === 'yearly' && !$isPro);
                    $isTrial = ($interval === 'trial' || $price == 0);

                    $featureList = [
                        ['text' => ($features['teams_limit'] ?? 10) . ' Teams Limit', 'icon' => 'bi-check-circle-fill text-success', 'isPro' => false],
                        ['text' => ($features['player_limit'] ?? $features['player_limits'] ?? 100) . ' Player Roster Capacity', 'icon' => 'bi-check-circle-fill text-success', 'isPro' => false],
                        ['text' => 'Multi-Sport Operations', 'icon' => 'bi-check-circle-fill text-success', 'isPro' => false],
                        ['text' => 'Public Club Website', 'icon' => 'bi-check-circle-fill text-success', 'isPro' => false],
                    ];

                    if ($isPro) {
                        $featureList = array_merge($featureList, [
                            ['text' => 'Custom Domain (www.myclub.co.ke)', 'icon' => 'bi-globe text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Controlled Video Uploads (2 GB)', 'icon' => 'bi-camera-video text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Club Media Center (5 GB)', 'icon' => 'bi-folder-symlink text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Mobile Digital Club Card', 'icon' => 'bi-person-vcard text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Instant SVG QR Codes', 'icon' => 'bi-qr-code text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Advanced Stats, Standings & Competitions', 'icon' => 'bi-bar-chart-line text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Advanced News & Social OG Metadata', 'icon' => 'bi-newspaper text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Data Exports (CSV & JSON)', 'icon' => 'bi-file-earmark-arrow-down text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Additional Admins & Priority Support', 'icon' => 'bi-people text-primary fw-bold', 'isPro' => true],
                            ['text' => 'Remove Benchero Branding', 'icon' => 'bi-eye-slash text-primary fw-bold', 'isPro' => true],
                        ]);
                    }

                    $visibleCount = 4;
                    $totalFeatures = count($featureList);
                    $hasExtra = $totalFeatures > $visibleCount;
                ?>
                <div class="col-12 col-md-6 col-lg-3">
                    <div class="card pricing-card border-0 shadow-sm rounded-4 p-4 <?= $isPro ? 'border border-primary border-2 shadow-lg bg-white' : ($isYearly ? 'border border-success border-2 shadow-sm bg-white' : 'bg-white') ?>">
                        <div class="pricing-card-header mb-3">
                            <?php if ($isPro): ?>
                                <div class="mb-2">
                                    <span class="badge bg-primary text-white px-3 py-1.5 rounded-pill fw-bold pricing-badge">
                                        <i class="bi bi-star-fill me-1"></i>BENCHERO PRO
                                    </span>
                                </div>
                            <?php elseif ($isYearly): ?>
                                <div class="mb-2">
                                    <span class="badge bg-success text-white px-3 py-1.5 rounded-pill fw-bold pricing-badge">
                                        <i class="bi bi-piggy-bank me-1"></i>SAVE KSh 2,000
                                    </span>
                                </div>
                            <?php elseif ($isTrial): ?>
                                <div class="mb-2">
                                    <span class="badge bg-secondary text-white px-3 py-1.5 rounded-pill pricing-badge">
                                        14-Day Free Access
                                    </span>
                                </div>
                            <?php endif; ?>

                            <h4 class="fw-bold mb-1 fs-5 text-dark pricing-card-title"><?= htmlspecialchars($plan['name']) ?></h4>
                            <p class="text-muted small mb-0 pricing-card-desc"><?= htmlspecialchars($features['description'] ?? '') ?></p>
                        </div>

                        <div class="pricing-card-price mb-3">
                            <div class="d-flex align-items-baseline gap-1 flex-wrap">
                                <span class="fs-2 fw-extrabold text-dark tracking-tight">KSh <?= number_format($price) ?></span>
                                <span class="text-muted small">
                                    <?php if ($isTrial): ?>
                                        / trial
                                    <?php else: ?>
                                        / <?= htmlspecialchars($interval) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>

                        <div class="pricing-card-highlight mb-3">
                            <?php if ($isPro): ?>
                                <div class="alert alert-primary py-2 px-3 rounded-3 small mb-0 text-primary border-0 fw-semibold">
                                    <i class="bi bi-patch-check-fill me-1"></i> Build your club's complete digital presence.
                                </div>
                            <?php elseif ($isYearly): ?>
                                <div class="alert alert-success py-2 px-3 rounded-3 small mb-0 text-success border-0 fw-semibold">
                                    <i class="bi bi-check-circle-fill me-1"></i> Pay <strong>KSh 10,000/year</strong> instead of KSh 12,000!
                                </div>
                            <?php else: ?>
                                <div class="text-muted small">
                                    Standard sports management access
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="pricing-features mb-4" data-visible-features="4">
                            <ul class="list-unstyled d-grid gap-2 mb-0 text-secondary small pricing-features-list">
                                <?php foreach (array_slice($featureList, 0, $visibleCount) as $item): ?>
                                    <li class="d-flex align-items-center gap-2 <?= $item['isPro'] ? 'text-primary fw-bold' : '' ?>">
                                        <i class="bi <?= $item['icon'] ?>"></i>
                                        <span><?= htmlspecialchars($item['text']) ?></span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>

                            <?php if ($hasExtra): ?>
                                <div class="pricing-features-extra collapse-container mt-2">
                                    <ul class="list-unstyled d-grid gap-2 mb-0 text-secondary small">
                                        <?php foreach (array_slice($featureList, $visibleCount) as $item): ?>
                                            <li class="d-flex align-items-center gap-2 <?= $item['isPro'] ? 'text-primary fw-bold' : '' ?>">
                                                <i class="bi <?= $item['icon'] ?>"></i>
                                                <span><?= htmlspecialchars($item['text']) ?></span>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                                <button type="button" 
                                        class="btn btn-link btn-sm p-0 mt-2 text-decoration-none fw-semibold text-primary features-toggle-btn d-inline-flex align-items-center gap-1"
                                        aria-expanded="false">
                                    <span>View more</span>
                                    <i class="bi bi-chevron-down toggle-icon"></i>
                                </button>
                            <?php endif; ?>
                        </div>

                        <div class="pricing-card-action mt-auto pt-2">
                            <a href="<?= url('/register?plan=' . urlencode($plan['slug'])) ?>" class="btn <?= $isPro ? 'btn-primary' : ($isYearly ? 'btn-success' : ($isTrial ? 'btn-outline-primary' : 'btn-dark')) ?> w-100 fw-bold py-2.5 rounded-3 shadow-sm">
                                <?php if ($isTrial): ?>
                                    Start Free Trial
                                <?php elseif ($isPro): ?>
                                    Choose Benchero Pro
                                <?php elseif ($isYearly): ?>
                                    Choose Standard Yearly
                                <?php else: ?>
                                    Choose Standard Monthly
                                <?php endif; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleButtons = document.querySelectorAll('.features-toggle-btn');

    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const cardFeatures = this.closest('.pricing-features');
            const extraContainer = cardFeatures.querySelector('.pricing-features-extra');
            const labelSpan = this.querySelector('span');
            const isExpanded = this.getAttribute('aria-expanded') === 'true';

            if (isExpanded) {
                this.setAttribute('aria-expanded', 'false');
                extraContainer.classList.remove('is-expanded');
                labelSpan.textContent = 'View more';
            } else {
                this.setAttribute('aria-expanded', 'true');
                extraContainer.classList.add('is-expanded');
                labelSpan.textContent = 'View less';
            }
        });
    });
});
</script>
