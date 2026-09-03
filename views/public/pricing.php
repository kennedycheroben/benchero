<?php $this->layout('layout', ['title' => $title]) ?>

<section class="py-5 bg-white border-bottom text-center">
    <div class="container py-lg-4">
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill mb-3">Transparent Pricing</span>
        <h1 class="display-5 fw-extrabold mb-3">Affordable Plans for Every Club</h1>
        <p class="lead text-muted max-w-2xl mx-auto mb-4">Choose the right capacity for your sports organization. Upgrade or cancel anytime.</p>
        
        <div class="d-inline-flex align-items-center bg-light p-1 rounded-pill border mb-4">
            <button type="button" id="btnMonthly" class="btn btn-sm btn-primary rounded-pill px-4 fw-bold" onclick="setBillingPeriod('monthly')">Monthly</button>
            <button type="button" id="btnAnnual" class="btn btn-sm text-muted rounded-pill px-4 fw-bold" onclick="setBillingPeriod('annual')">Annual (Save 20%)</button>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <?php foreach ($plans as $plan): ?>
                <?php 
                    $features = json_decode($plan['features'] ?? '{}', true);
                    $priceMonthly = (float)($plan['price_kes'] ?? 0);
                    $priceAnnual = round($priceMonthly * 12 * 0.8, 2);
                ?>
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm rounded-4 p-4 <?= $plan['slug'] === 'starter' ? 'border border-primary border-2' : '' ?>">
                        <?php if ($plan['slug'] === 'starter'): ?>
                            <span class="badge bg-primary text-white position-absolute top-0 end-0 m-3 px-3 py-1 rounded-pill">Most Popular</span>
                        <?php endif; ?>
                        
                        <h4 class="fw-bold mb-1"><?= htmlspecialchars($plan['name']) ?></h4>
                        <div class="my-3">
                            <span class="display-6 fw-extrabold price-monthly">KES <?= number_format($priceMonthly) ?></span>
                            <span class="display-6 fw-extrabold price-annual d-none">KES <?= number_format($priceAnnual) ?></span>
                            <span class="text-muted small interval-text">/ month</span>
                        </div>
                        
                        <ul class="list-unstyled d-grid gap-2 mb-4 text-secondary small">
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $features['teams_limit'] ?? 2 ?> Teams Limit</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> <?= $features['player_limits'] ?? 25 ?> Player Roster Capacity</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Multi-Sport Access</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Fixtures & Results Tracking</li>
                            <li class="d-flex align-items-center gap-2"><i class="bi bi-check-circle-fill text-success"></i> Public Club Page</li>
                        </ul>
                        
                        <a href="<?= url('/register?plan=' . urlencode($plan['slug'])) ?>" class="btn <?= $plan['slug'] === 'starter' ? 'btn-primary' : 'btn-outline-primary' ?> w-100 fw-bold py-2 rounded-3 mt-auto">
                            <?= $priceMonthly == 0 ? 'Start Free Trial' : 'Select Plan' ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
function setBillingPeriod(period) {
    const monthlyEls = document.querySelectorAll('.price-monthly');
    const annualEls = document.querySelectorAll('.price-annual');
    const intervalEls = document.querySelectorAll('.interval-text');
    const btnM = document.getElementById('btnMonthly');
    const btnA = document.getElementById('btnAnnual');

    if (period === 'annual') {
        monthlyEls.forEach(el => el.classList.add('d-none'));
        annualEls.forEach(el => el.classList.remove('d-none'));
        intervalEls.forEach(el => el.textContent = ' / year');
        btnA.className = 'btn btn-sm btn-primary rounded-pill px-4 fw-bold';
        btnM.className = 'btn btn-sm text-muted rounded-pill px-4 fw-bold';
    } else {
        monthlyEls.forEach(el => el.classList.remove('d-none'));
        annualEls.forEach(el => el.classList.add('d-none'));
        intervalEls.forEach(el => el.textContent = ' / month');
        btnM.className = 'btn btn-sm btn-primary rounded-pill px-4 fw-bold';
        btnA.className = 'btn btn-sm text-muted rounded-pill px-4 fw-bold';
    }
}
</script>
