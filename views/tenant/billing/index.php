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

    // Index all available paid plans by ID from database
    $planMap = [];
    if (!empty($plans)) {
        foreach ($plans as $p) {
            $planMap[(int)$p['id']] = $p;
        }
    }

    $stdMonthly = $planMap[2] ?? [
        'id' => 2,
        'name' => 'Standard Monthly',
        'slug' => 'standard-monthly',
        'price_kes' => 1000.00,
        'billing_interval' => 'monthly',
        'intl_pricing' => \Benchero\Core\PricingConfig::getInternationalPrice(2, 'USD'),
    ];
    $stdYearly = $planMap[3] ?? [
        'id' => 3,
        'name' => 'Standard Yearly',
        'slug' => 'standard-yearly',
        'price_kes' => 10000.00,
        'billing_interval' => 'yearly',
        'intl_pricing' => \Benchero\Core\PricingConfig::getInternationalPrice(3, 'USD'),
    ];
    $proMonthly = $planMap[5] ?? [
        'id' => 5,
        'name' => 'Benchero Pro Monthly',
        'slug' => 'pro-monthly',
        'price_kes' => 2500.00,
        'billing_interval' => 'monthly',
        'intl_pricing' => \Benchero\Core\PricingConfig::getInternationalPrice(5, 'USD'),
    ];
    $proYearly = $planMap[4] ?? [
        'id' => 4,
        'name' => 'Benchero Pro Yearly',
        'slug' => 'benchero-pro',
        'price_kes' => 25000.00,
        'billing_interval' => 'yearly',
        'intl_pricing' => \Benchero\Core\PricingConfig::getInternationalPrice(4, 'USD'),
    ];

    $activeMethod = ($defaultMethod ?? 'mpesa') === 'paypal' ? 'paypal' : 'mpesa';
    $activePlanId = (int)($selectedPlanId ?? 5);
?>

<style>
.checkout-card {
    border: 1px solid #e2e8f0;
}
.cursor-pointer {
    cursor: pointer;
}
.fs-7 { font-size: 0.8125rem; }
.fs-8 { font-size: 0.75rem; }

/* Plan Option Cards */
.plan-option-card {
    border: 2px solid #e2e8f0 !important;
    background-color: #ffffff;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    user-select: none;
}
.plan-option-card:hover {
    border-color: #cbd5e1 !important;
    transform: translateY(-1px);
}
.plan-option-card.selected {
    border-color: var(--benchero-accent, #2563eb) !important;
    background-color: #f0f7ff !important;
    box-shadow: 0 4px 14px rgba(37, 99, 235, 0.08);
}
.plan-option-card .plan-check-icon {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.plan-option-card.selected .plan-check-icon {
    opacity: 1 !important;
    transform: scale(1);
}
.plan-option-card:not(.selected) .plan-check-icon {
    opacity: 0 !important;
    transform: scale(0.7);
}

/* Method Option Cards */
.method-option-card {
    border: 2px solid #e2e8f0 !important;
    background-color: #ffffff;
    transition: border-color 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
    user-select: none;
}
.method-option-card:hover {
    border-color: #cbd5e1 !important;
    transform: translateY(-1px);
}
.method-option-card.selected.method-mpesa {
    border-color: #16a34a !important;
    background-color: #f0fdf4 !important;
    box-shadow: 0 4px 14px rgba(22, 163, 74, 0.08);
}
.method-option-card.selected.method-paypal {
    border-color: #0070ba !important;
    background-color: #f0f7ff !important;
    box-shadow: 0 4px 14px rgba(0, 112, 186, 0.08);
}
.method-option-card .method-check-icon {
    transition: opacity 0.15s ease, transform 0.15s ease;
}
.method-option-card.selected .method-check-icon {
    opacity: 1 !important;
    transform: scale(1);
}
.method-option-card:not(.selected) .method-check-icon {
    opacity: 0 !important;
    transform: scale(0.7);
}

.method-icon {
    width: 38px;
    height: 38px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}
.mpesa-icon {
    background-color: #dcfce7;
}
.paypal-icon {
    background-color: #e0f2fe;
}

/* Form inputs & buttons */
#mpesa_phone:focus {
    border-color: #16a34a;
    box-shadow: 0 0 0 0.25rem rgba(22, 163, 74, 0.15);
}
.btn-pay-mpesa {
    background-color: #16a34a;
    border-color: #16a34a;
    color: #ffffff;
}
.btn-pay-mpesa:hover, .btn-pay-mpesa:focus {
    background-color: #15803d;
    border-color: #15803d;
    color: #ffffff;
}
.btn-pay-paypal {
    background-color: #0070ba;
    border-color: #0070ba;
    color: #ffffff;
}
.btn-pay-paypal:hover, .btn-pay-paypal:focus {
    background-color: #005ea6;
    border-color: #005ea6;
    color: #ffffff;
}

/* Filter Buttons */
.btn-plan-filter {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
    border: none;
    background: transparent;
    transition: all 0.2s ease;
}
.btn-plan-filter:hover {
    color: #0f172a;
}
.btn-plan-filter.active {
    background-color: #0f172a !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.15);
}

/* Feature Comparison Table */
.comparison-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}
.comparison-table th {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.comparison-table td {
    font-size: 0.8125rem;
    vertical-align: middle;
}

.plan-option-card .plan-savings-badge {
    font-size: 0.7rem;
    letter-spacing: 0.02em;
    font-weight: 700;
}

@media (max-width: 575.98px) {
    .checkout-card {
        padding: 1.25rem !important;
    }
}
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="fw-bold mb-1">Billing & Subscription</h2>
        <p class="text-muted small mb-0">Choose your plan and payment method to manage your Benchero subscription.</p>
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

<div class="row g-4 align-items-start mb-4">
    <!-- Left Column: Primary Checkout Interface -->
    <div class="col-lg-7" id="checkoutSection">
        <div class="card border-0 shadow-sm rounded-4 p-4 p-md-5 bg-white checkout-card">
            <!-- Dynamic Alert Box -->
            <div id="checkoutAlert" class="alert d-none rounded-3 mb-4"></div>

            <!-- Processing Status Spinner -->
            <div id="statusContainer" class="d-none text-center py-5">
                <div class="spinner-border text-primary mb-3" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h4 class="fw-bold mb-2 text-dark" id="statusTitle">Payment Processing</h4>
                <p class="text-muted mb-3" id="statusMsg">Please authorize the payment prompt on your device.</p>
                <div class="badge bg-warning-subtle text-warning border border-warning px-3 py-2 text-uppercase fs-7 rounded-pill mb-2" id="statusBadge">
                    Waiting for confirmation...
                </div>
                <p class="text-muted small mb-0">Please do not close this window.</p>
            </div>

            <!-- Checkout Form Content -->
            <div id="checkoutFormContent">
                <!-- 1. Choose your plan -->
                <div class="mb-4">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <label class="form-label fw-bold text-dark fs-6 mb-0">Choose your plan</label>
                            <div class="fs-8 text-muted">Select the tier and billing cycle for your club</div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <!-- Quick Filter Pills -->
                            <div class="btn-group btn-group-sm p-1 bg-light rounded-pill border" role="group" id="planFilterGroup">
                                <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-plan-filter active" data-filter="all">All Plans</button>
                                <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-plan-filter" data-filter="standard">Standard</button>
                                <button type="button" class="btn btn-sm rounded-pill px-2.5 py-1 btn-plan-filter" data-filter="pro">Pro</button>
                            </div>
                        </div>
                    </div>

                    <!-- Standard Tier Section -->
                    <div class="plan-tier-section mb-3" data-tier="standard">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-secondary-subtle text-secondary-emphasis border border-secondary-subtle rounded-pill fs-8 fw-bold">STANDARD</span>
                                <span class="fs-8 text-muted d-none d-sm-inline">Club essentials: 10 teams, 100-500 players, website</span>
                            </div>
                        </div>
                        <div class="row g-2 g-sm-3">
                            <!-- Standard Monthly (ID 2) -->
                            <div class="col-12 col-sm-6">
                                <label class="plan-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer <?= $activePlanId === 2 ? 'selected' : '' ?>" for="plan_2" id="plan_card_2">
                                    <input class="visually-hidden plan-radio" type="radio" name="plan_id" id="plan_2" value="2"
                                        data-plan-name="Standard Monthly"
                                        data-price-kes="<?= (float)($stdMonthly['price_kes'] ?? 1000) ?>"
                                        data-price-usd="<?= (float)($stdMonthly['intl_pricing']['charged_amount'] ?? 8) ?>"
                                        data-interval="month"
                                        <?= $activePlanId === 2 ? 'checked' : '' ?> required>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-bold text-dark">Standard Monthly</span>
                                        <span class="plan-check-icon text-primary <?= $activePlanId === 2 ? '' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                    </div>
                                    <div class="fw-bold text-dark fs-5 mb-1 plan-card-price" id="plan_2_price">
                                        <?= ($activeMethod === 'paypal') ? '$8.00 / month' : 'KSh 1,000 / month' ?>
                                    </div>
                                    <div class="fs-8 text-muted">Up to 100 players & 10 teams</div>
                                </label>
                            </div>

                            <!-- Standard Yearly (ID 3) -->
                            <div class="col-12 col-sm-6">
                                <label class="plan-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer <?= $activePlanId === 3 ? 'selected' : '' ?>" for="plan_3" id="plan_card_3">
                                    <input class="visually-hidden plan-radio" type="radio" name="plan_id" id="plan_3" value="3"
                                        data-plan-name="Standard Yearly"
                                        data-price-kes="<?= (float)($stdYearly['price_kes'] ?? 10000) ?>"
                                        data-price-usd="<?= (float)($stdYearly['intl_pricing']['charged_amount'] ?? 80) ?>"
                                        data-interval="year"
                                        <?= $activePlanId === 3 ? 'checked' : '' ?> required>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="fw-bold text-dark">Standard Yearly</span>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill plan-savings-badge"
                                                data-savings-kes="Save KSh 2,000 • 16.67% off" data-savings-usd="Save $16.00 • 16.67% off">
                                                <?= ($activeMethod === 'paypal') ? 'Save $16.00 • 16.67% off' : 'Save KSh 2,000 • 16.67% off' ?>
                                            </span>
                                        </div>
                                        <span class="plan-check-icon text-primary <?= $activePlanId === 3 ? '' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                    </div>
                                    <div class="fw-bold text-dark fs-5 mb-1 plan-card-price" id="plan_3_price">
                                        <?= ($activeMethod === 'paypal') ? '$80.00 / year' : 'KSh 10,000 / year' ?>
                                    </div>
                                    <div class="fs-8 text-muted">Up to 500 players & 25 teams</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Benchero Pro Tier Section -->
                    <div class="plan-tier-section mb-3" data-tier="pro">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary text-white rounded-pill fs-8 fw-bold">
                                    <i class="bi bi-star-fill text-warning me-1"></i>BENCHERO PRO
                                </span>
                                <span class="fs-8 text-muted d-none d-sm-inline">Unlimited teams, domain, video, cards & branding removal</span>
                            </div>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill fs-8">Recommended</span>
                        </div>
                        <div class="row g-2 g-sm-3">
                            <!-- Pro Monthly (ID 5) -->
                            <div class="col-12 col-sm-6">
                                <label class="plan-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer <?= $activePlanId === 5 ? 'selected' : '' ?>" for="plan_5" id="plan_card_5">
                                    <input class="visually-hidden plan-radio" type="radio" name="plan_id" id="plan_5" value="5"
                                        data-plan-name="Benchero Pro Monthly"
                                        data-price-kes="<?= (float)($proMonthly['price_kes'] ?? 2500) ?>"
                                        data-price-usd="<?= (float)($proMonthly['intl_pricing']['charged_amount'] ?? 20) ?>"
                                        data-interval="month"
                                        <?= $activePlanId === 5 ? 'checked' : '' ?> required>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="fw-bold text-dark">Pro Monthly</span>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill plan-savings-badge">
                                                All Pro
                                            </span>
                                        </div>
                                        <span class="plan-check-icon text-primary <?= $activePlanId === 5 ? '' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                    </div>
                                    <div class="fw-bold text-dark fs-5 mb-1 plan-card-price" id="plan_5_price">
                                        <?= ($activeMethod === 'paypal') ? '$20.00 / month' : 'KSh 2,500 / month' ?>
                                    </div>
                                    <div class="fs-8 text-muted">Unlimited players & full pro suite</div>
                                </label>
                            </div>

                            <!-- Pro Yearly (ID 4) -->
                            <div class="col-12 col-sm-6">
                                <label class="plan-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer <?= $activePlanId === 4 ? 'selected' : '' ?>" for="plan_4" id="plan_card_4">
                                    <input class="visually-hidden plan-radio" type="radio" name="plan_id" id="plan_4" value="4"
                                        data-plan-name="Benchero Pro Yearly"
                                        data-price-kes="<?= (float)($proYearly['price_kes'] ?? 25000) ?>"
                                        data-price-usd="<?= (float)($proYearly['intl_pricing']['charged_amount'] ?? 200) ?>"
                                        data-interval="year"
                                        <?= $activePlanId === 4 ? 'checked' : '' ?> required>
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="d-flex align-items-center gap-1.5 flex-wrap">
                                            <span class="fw-bold text-dark">Pro Yearly</span>
                                            <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill plan-savings-badge"
                                                data-savings-kes="Best Value • Save KSh 5,000 • 16.67% off" data-savings-usd="Best Value • Save $40.00 • 16.67% off">
                                                <?= ($activeMethod === 'paypal') ? 'Best Value • Save $40.00 • 16.67% off' : 'Best Value • Save KSh 5,000 • 16.67% off' ?>
                                            </span>
                                        </div>
                                        <span class="plan-check-icon text-primary <?= $activePlanId === 4 ? '' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                    </div>
                                    <div class="fw-bold text-dark fs-5 mb-1 plan-card-price" id="plan_4_price">
                                        <?= ($activeMethod === 'paypal') ? '$200.00 / year' : 'KSh 25,000 / year' ?>
                                    </div>
                                    <div class="fs-8 text-muted">Unlimited players • 2 months free</div>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Collapsible Feature Comparison -->
                    <div class="mt-2">
                        <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none fw-semibold text-primary d-inline-flex align-items-center gap-1" data-bs-toggle="collapse" data-bs-target="#planFeatureComparison" aria-expanded="false">
                            <i class="bi bi-layout-text-window-reverse"></i>
                            <span>Compare Standard vs Pro features</span>
                            <i class="bi bi-chevron-down fs-8"></i>
                        </button>
                        <div class="collapse mt-2" id="planFeatureComparison">
                            <div class="card card-body comparison-card p-3 rounded-3 shadow-none border">
                                <div class="table-responsive">
                                    <table class="table table-sm table-borderless comparison-table mb-0">
                                        <thead>
                                            <tr class="border-bottom">
                                                <th class="text-muted fw-bold">Feature</th>
                                                <th class="text-dark fw-bold text-center" style="width: 35%;">Standard</th>
                                                <th class="text-primary fw-bold text-center" style="width: 35%;">Benchero Pro</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Player Capacity</td>
                                                <td class="text-center fw-semibold">100 - 500 Players</td>
                                                <td class="text-center fw-bold text-primary"><i class="bi bi-infinity me-1"></i>Unlimited</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Team Capacity</td>
                                                <td class="text-center fw-semibold">10 - 25 Teams</td>
                                                <td class="text-center fw-bold text-primary"><i class="bi bi-infinity me-1"></i>Unlimited</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Public Club Website</td>
                                                <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Included</td>
                                                <td class="text-center text-success"><i class="bi bi-check-circle-fill"></i> Included</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Custom Domain (www.myclub.com)</td>
                                                <td class="text-center text-muted"><i class="bi bi-dash"></i></td>
                                                <td class="text-center text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Included</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Video Uploads & Storage</td>
                                                <td class="text-center text-muted"><i class="bi bi-dash"></i></td>
                                                <td class="text-center text-success fw-bold"><i class="bi bi-check-circle-fill"></i> 2 GB Storage</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Club Media Center</td>
                                                <td class="text-center text-muted"><i class="bi bi-dash"></i></td>
                                                <td class="text-center text-success fw-bold"><i class="bi bi-check-circle-fill"></i> 5 GB Storage</td>
                                            </tr>
                                            <tr class="border-bottom">
                                                <td class="text-muted">Mobile Digital Club Cards & QR</td>
                                                <td class="text-center text-muted"><i class="bi bi-dash"></i></td>
                                                <td class="text-center text-success fw-bold"><i class="bi bi-check-circle-fill"></i> Included</td>
                                            </tr>
                                            <tr>
                                                <td class="text-muted">Benchero Branding</td>
                                                <td class="text-center text-muted">Benchero Badge</td>
                                                <td class="text-center text-success fw-bold"><i class="bi bi-check-circle-fill"></i> 100% Removed</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Choose Payment Method -->
                <div class="mb-4">
                    <label class="form-label fw-bold text-dark fs-6 mb-2">Payment method</label>
                    <div class="row g-3">
                        <!-- M-Pesa -->
                        <div class="col-12 col-sm-6">
                            <label class="method-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer method-mpesa <?= $activeMethod === 'mpesa' ? 'selected' : '' ?>" id="method_card_mpesa" for="method_mpesa">
                                <input class="visually-hidden method-radio" type="radio" name="payment_method" id="method_mpesa" value="mpesa" <?= $activeMethod === 'mpesa' ? 'checked' : '' ?>>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="method-icon mpesa-icon"><i class="bi bi-phone-fill fs-5 text-success"></i></span>
                                        <div>
                                            <span class="fw-bold d-block text-dark">M-Pesa</span>
                                            <span class="fs-8 text-muted">Kenya</span>
                                        </div>
                                    </div>
                                    <span class="method-check-icon <?= $activeMethod === 'mpesa' ? 'text-success' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                </div>
                            </label>
                        </div>

                        <!-- PayPal -->
                        <div class="col-12 col-sm-6">
                            <label class="method-option-card d-block p-3 rounded-3 border h-100 position-relative cursor-pointer method-paypal <?= $activeMethod === 'paypal' ? 'selected' : '' ?>" id="method_card_paypal" for="method_paypal">
                                <input class="visually-hidden method-radio" type="radio" name="payment_method" id="method_paypal" value="paypal" <?= $activeMethod === 'paypal' ? 'checked' : '' ?>>
                                <div class="d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="method-icon paypal-icon"><i class="bi bi-paypal fs-5 text-primary"></i></span>
                                        <div>
                                            <span class="fw-bold d-block text-dark">PayPal</span>
                                            <span class="fs-8 text-muted">International</span>
                                        </div>
                                    </div>
                                    <span class="method-check-icon <?= $activeMethod === 'paypal' ? 'text-primary' : 'opacity-0' ?>"><i class="bi bi-check-circle-fill fs-5"></i></span>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <!-- 3. Summary & Payment Action -->
                <div class="p-3 p-md-4 rounded-3 bg-light border mb-0">
                    <div class="d-flex justify-content-between align-items-baseline mb-3 pb-2 border-bottom">
                        <span class="text-muted fw-semibold" id="summaryPlanName">Pro Monthly</span>
                        <span class="fs-4 fw-bold text-dark" id="summaryPriceDisplay">
                            <?= ($activeMethod === 'paypal') ? '$20.00 / month' : 'KSh 2,500 / month' ?>
                        </span>
                    </div>

                    <!-- M-Pesa Input & Pay CTA -->
                    <div id="mpesaPaymentArea" class="<?= $activeMethod === 'mpesa' ? '' : 'd-none' ?>">
                        <div class="mb-3">
                            <label for="mpesa_phone" class="form-label fw-semibold text-dark small mb-1">M-Pesa number</label>
                            <input type="tel" id="mpesa_phone" name="mpesa_phone" class="form-control form-control-lg font-monospace" placeholder="07XXXXXXXX" autocomplete="tel">
                            <div class="form-text fs-8 text-muted mt-1">An instant STK PIN prompt will appear on your phone.</div>
                        </div>
                        <button type="button" id="btnPayMpesa" class="btn btn-pay-mpesa btn-lg w-100 fw-bold rounded-3 shadow-sm py-2.5">
                            <i class="bi bi-phone me-1"></i> Pay with M-Pesa
                        </button>
                    </div>

                    <!-- PayPal Pay CTA -->
                    <div id="paypalPaymentArea" class="<?= $activeMethod === 'paypal' ? '' : 'd-none' ?>">
                        <button type="button" id="btnPayPayPal" class="btn btn-pay-paypal btn-lg w-100 fw-bold rounded-3 shadow-sm py-2.5">
                            <i class="bi bi-paypal me-1"></i> Pay with PayPal
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Current Status & Payment History -->
    <div class="col-lg-5">
        <!-- Current Subscription Status -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white mb-4">
            <h6 class="fw-bold text-dark mb-3">Current Subscription Status</h6>

            <div class="d-flex align-items-center justify-content-between p-3 bg-light rounded-3 mb-3">
                <div>
                    <div class="small text-muted">Current Plan</div>
                    <div class="fw-bold fs-6"><?= htmlspecialchars($sub['plan_name']) ?></div>
                </div>
                <div>
                    <span class="badge bg-<?= $sub['status'] === 'ACTIVE' ? 'success' : ($sub['status'] === 'TRIAL' ? 'info' : 'danger') ?> px-2.5 py-1.5 text-uppercase fs-8">
                        <?= htmlspecialchars($sub['status']) ?>
                    </span>
                </div>
            </div>

            <div class="row g-2 small text-muted mb-0">
                <div class="col-6">
                    <div class="fs-8 text-muted">Started</div>
                    <div class="fw-semibold text-dark"><?= !empty($sub['starts_at']) ? date('M j, Y', strtotime($sub['starts_at'])) : '—' ?></div>
                </div>
                <div class="col-6">
                    <div class="fs-8 text-muted">Expires</div>
                    <div class="fw-semibold text-dark"><?= !empty($sub['expires_at']) ? date('M j, Y', strtotime($sub['expires_at'])) : '—' ?></div>
                </div>
                <div class="col-12 mt-2">
                    <div class="fs-8 text-muted">Days Remaining</div>
                    <div class="fw-bold <?= $sub['days_remaining'] <= 3 ? 'text-danger' : 'text-dark' ?>">
                        <?= (int)$sub['days_remaining'] ?> <?= (int)$sub['days_remaining'] === 1 ? 'day' : 'days' ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment History -->
        <div class="card border-0 shadow-sm rounded-4 p-4 bg-white">
            <h6 class="fw-bold text-dark mb-3">Payment History</h6>
            <?php if (empty($payments)): ?>
                <div class="text-center py-4 text-muted small">
                    <i class="bi bi-receipt display-6 d-block mb-2 text-secondary opacity-50"></i>
                    No payments recorded yet.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="fs-8 text-muted">Date</th>
                                <th class="fs-8 text-muted">Amount</th>
                                <th class="fs-8 text-muted">Method</th>
                                <th class="fs-8 text-muted">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $pay): ?>
                                <tr>
                                    <td class="small text-nowrap"><?= date('M j, Y', strtotime($pay['created_at'])) ?></td>
                                    <td class="small fw-bold">
                                        <?php if (($pay['currency'] ?? 'KES') === 'USD'): ?>
                                            $<?= number_format($pay['amount'], 2) ?>
                                        <?php else: ?>
                                            KSh <?= number_format($pay['amount']) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($pay['provider'] ?? '') === 'paypal' || ($pay['payment_method'] ?? '') === 'paypal'): ?>
                                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-8">
                                                PayPal
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle fs-8">
                                                M-Pesa
                                            </span>
                                        <?php endif; ?>
                                    </td>
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    const checkoutAlert = document.getElementById('checkoutAlert');
    const statusContainer = document.getElementById('statusContainer');
    const checkoutFormContent = document.getElementById('checkoutFormContent');
    const statusTitle = document.getElementById('statusTitle');
    const statusMsg = document.getElementById('statusMsg');
    const statusBadge = document.getElementById('statusBadge');

    const btnPayMpesa = document.getElementById('btnPayMpesa');
    const btnPayPayPal = document.getElementById('btnPayPayPal');
    const mpesaPaymentArea = document.getElementById('mpesaPaymentArea');
    const paypalPaymentArea = document.getElementById('paypalPaymentArea');

    const summaryPlanName = document.getElementById('summaryPlanName');
    const summaryPriceDisplay = document.getElementById('summaryPriceDisplay');
    const methodCardMpesa = document.getElementById('method_card_mpesa');
    const methodCardPaypal = document.getElementById('method_card_paypal');
    const csrfToken = '<?= csrf_token() ?>';

    let pollInterval = null;
    let pollCount = 0;
    const maxPolls = 35; // 35 * 3s = ~105s

    function getSelectedMethod() {
        const checkedMethod = document.querySelector('input.method-radio:checked');
        return checkedMethod ? checkedMethod.value : 'mpesa';
    }

    function getSelectedPlan() {
        const checkedPlan = document.querySelector('input.plan-radio:checked');
        if (!checkedPlan) return null;
        return {
            id: checkedPlan.value,
            name: checkedPlan.dataset.planName,
            priceKes: parseFloat(checkedPlan.dataset.priceKes || 0),
            priceUsd: parseFloat(checkedPlan.dataset.priceUsd || 0),
            interval: checkedPlan.dataset.interval || 'month'
        };
    }

    function updateUI() {
        const method = getSelectedMethod();
        const plan = getSelectedPlan();
        if (!plan) return;

        // 1. Update Plan Cards Visual States
        document.querySelectorAll('.plan-option-card').forEach(card => {
            const radio = card.querySelector('input.plan-radio');
            if (radio && radio.value === String(plan.id)) {
                card.classList.add('selected');
                const check = card.querySelector('.plan-check-icon');
                if (check) check.classList.remove('opacity-0');
            } else {
                card.classList.remove('selected');
                const check = card.querySelector('.plan-check-icon');
                if (check) check.classList.add('opacity-0');
            }
        });

        // 2. Update Payment Method Cards Visual States & Display Areas
        if (method === 'mpesa') {
            methodCardMpesa.classList.add('selected');
            methodCardPaypal.classList.remove('selected');
            mpesaPaymentArea.classList.remove('d-none');
            paypalPaymentArea.classList.add('d-none');

            // Update all plan cards to KES
            document.querySelectorAll('.plan-option-card').forEach(card => {
                const radio = card.querySelector('input.plan-radio');
                const priceEl = card.querySelector('.plan-card-price');
                const savingsEl = card.querySelector('.plan-savings-badge');
                if (!radio || !priceEl) return;

                const kes = parseFloat(radio.dataset.priceKes || 0);
                const interval = radio.dataset.interval || 'month';
                priceEl.innerHTML = `KSh ${Number(kes).toLocaleString()} <span class="fs-7 text-muted fw-normal">/ ${interval}</span>`;

                if (savingsEl && savingsEl.dataset.savingsKes) {
                    savingsEl.textContent = savingsEl.dataset.savingsKes;
                }
            });

            // Summary in KSh ONLY
            if (summaryPlanName) summaryPlanName.textContent = plan.name;
            const formattedKes = 'KSh ' + Number(plan.priceKes).toLocaleString();
            if (summaryPriceDisplay) summaryPriceDisplay.innerHTML = `${formattedKes} <span class="fs-7 text-muted fw-normal">/ ${plan.interval}</span>`;
        } else {
            methodCardPaypal.classList.add('selected');
            methodCardMpesa.classList.remove('selected');
            paypalPaymentArea.classList.remove('d-none');
            mpesaPaymentArea.classList.add('d-none');

            // Update all plan cards to USD
            document.querySelectorAll('.plan-option-card').forEach(card => {
                const radio = card.querySelector('input.plan-radio');
                const priceEl = card.querySelector('.plan-card-price');
                const savingsEl = card.querySelector('.plan-savings-badge');
                if (!radio || !priceEl) return;

                const usd = parseFloat(radio.dataset.priceUsd || 0);
                const interval = radio.dataset.interval || 'month';
                priceEl.innerHTML = `$${Number(usd).toFixed(2)} <span class="fs-7 text-muted fw-normal">/ ${interval}</span>`;

                if (savingsEl && savingsEl.dataset.savingsUsd) {
                    savingsEl.textContent = savingsEl.dataset.savingsUsd;
                }
            });

            // Summary in USD ONLY
            if (summaryPlanName) summaryPlanName.textContent = plan.name;
            const formattedUsd = '$' + Number(plan.priceUsd).toFixed(2);
            if (summaryPriceDisplay) summaryPriceDisplay.innerHTML = `${formattedUsd} <span class="fs-7 text-muted fw-normal">/ ${plan.interval}</span>`;
        }
    }

    // Attach Change Listeners to all radios
    document.querySelectorAll('input.plan-radio').forEach(r => {
        r.addEventListener('change', updateUI);
    });
    document.querySelectorAll('input.method-radio').forEach(r => {
        r.addEventListener('change', updateUI);
    });

    // Plan Filter Buttons
    document.querySelectorAll('.btn-plan-filter').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.btn-plan-filter').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const filter = this.dataset.filter;
            document.querySelectorAll('.plan-tier-section').forEach(sec => {
                if (filter === 'all' || sec.dataset.tier === filter) {
                    sec.classList.remove('d-none');
                } else {
                    sec.classList.add('d-none');
                }
            });
        });
    });

    // Initialize UI on load
    updateUI();

    function showAlert(type, msg) {
        checkoutAlert.className = `alert alert-${type} rounded-3 mb-4`;
        checkoutAlert.textContent = msg;
        checkoutAlert.classList.remove('d-none');
    }

    function hideAlert() {
        checkoutAlert.classList.add('d-none');
        checkoutAlert.textContent = '';
    }

    function showLoadingState(title, msg, badge) {
        hideAlert();
        checkoutFormContent.classList.add('d-none');
        statusContainer.classList.remove('d-none');
        statusTitle.textContent = title;
        statusMsg.textContent = msg;
        statusBadge.className = 'badge bg-warning-subtle text-warning border border-warning px-3 py-2 text-uppercase fs-7 rounded-pill mb-2';
        statusBadge.textContent = badge;
    }

    function showSuccessState(msg) {
        statusTitle.textContent = 'Payment Successful';
        statusMsg.textContent = msg;
        statusBadge.className = 'badge bg-success px-3 py-2 text-uppercase fs-7 rounded-pill mb-2';
        statusBadge.textContent = 'Active Subscription';
    }

    function showErrorState(msg) {
        statusTitle.textContent = 'Payment Unsuccessful';
        statusMsg.textContent = msg;
        statusBadge.className = 'badge bg-danger px-3 py-2 text-uppercase fs-7 rounded-pill mb-2';
        statusBadge.textContent = 'Failed';

        setTimeout(() => {
            statusContainer.classList.add('d-none');
            checkoutFormContent.classList.remove('d-none');
            if (btnPayMpesa) {
                btnPayMpesa.disabled = false;
                btnPayMpesa.innerHTML = '<i class="bi bi-phone me-1"></i> Pay with M-Pesa';
            }
            if (btnPayPayPal) {
                btnPayPayPal.disabled = false;
                btnPayPayPal.innerHTML = '<i class="bi bi-paypal me-1"></i> Pay with PayPal';
            }
            showAlert('danger', msg);
        }, 2800);
    }

    function normalizeKenyanPhone(phone) {
        const cleaned = phone.replace(/[\s\-\+]/g, '');
        if (/^254[71]\d{8}$/.test(cleaned)) {
            return cleaned;
        }
        if (/^0[71]\d{8}$/.test(cleaned)) {
            return '254' + cleaned.substring(1);
        }
        if (/^[71]\d{8}$/.test(cleaned)) {
            return '254' + cleaned;
        }
        return null;
    }

    // M-Pesa Checkout Action
    if (btnPayMpesa) {
        btnPayMpesa.addEventListener('click', function () {
            hideAlert();
            const phoneInput = document.getElementById('mpesa_phone');
            const phoneRaw = phoneInput ? phoneInput.value.trim() : '';
            const plan = getSelectedPlan();

            if (!phoneRaw) {
                showAlert('danger', 'Please enter your M-Pesa phone number.');
                if (phoneInput) phoneInput.focus();
                return;
            }

            const normalizedPhone = normalizeKenyanPhone(phoneRaw);
            if (!normalizedPhone) {
                showAlert('danger', 'Please enter a valid Kenyan phone number (e.g. 0712345678 or 254712345678).');
                if (phoneInput) phoneInput.focus();
                return;
            }

            if (!plan || !plan.id) {
                showAlert('danger', 'Please select a subscription plan.');
                return;
            }

            btnPayMpesa.disabled = true;
            btnPayMpesa.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Initiating STK Push...';

            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('phone_number', normalizedPhone);
            formData.append('plan_id', plan.id);
            formData.append('payment_method', 'mpesa');

            fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent') ?>', {
                method: 'POST',
                body: formData,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    btnPayMpesa.disabled = false;
                    btnPayMpesa.innerHTML = '<i class="bi bi-phone me-1"></i> Pay with M-Pesa';
                    showAlert('danger', data.error || 'Failed to prepare payment.');
                    return;
                }

                const intentId = data.intent_id;

                // Step 2: Send STK Push
                fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent/') ?>' + intentId + '/initiate', {
                    method: 'POST',
                    headers: { 
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-Token': csrfToken
                    }
                })
                .then(res => res.json())
                .then(initRes => {
                    if (initRes.status === 'completed_mock' || initRes.status === 'completed') {
                        showLoadingState('Payment Confirmed', 'Subscription activated successfully.', 'Active');
                        showSuccessState('Your Benchero subscription is now active!');
                        setTimeout(() => window.location.reload(), 1500);
                        return;
                    }

                    showLoadingState('M-Pesa Prompt Sent', 'Check your phone. Enter your PIN on the prompt.', 'Awaiting PIN Entry');
                    startPolling(intentId);
                })
                .catch(() => {
                    btnPayMpesa.disabled = false;
                    btnPayMpesa.innerHTML = '<i class="bi bi-phone me-1"></i> Pay with M-Pesa';
                    showAlert('danger', 'Network error during STK push initiation. Please try again.');
                });
            })
            .catch(() => {
                btnPayMpesa.disabled = false;
                btnPayMpesa.innerHTML = '<i class="bi bi-phone me-1"></i> Pay with M-Pesa';
                showAlert('danger', 'Network error preparing checkout. Please try again.');
            });
        });
    }

    // PayPal Checkout Action
    if (btnPayPayPal) {
        btnPayPayPal.addEventListener('click', function () {
            hideAlert();
            const plan = getSelectedPlan();

            if (!plan || !plan.id) {
                showAlert('danger', 'Please choose a subscription plan.');
                return;
            }

            btnPayPayPal.disabled = true;
            btnPayPayPal.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to PayPal...';

            const formData = new FormData();
            formData.append('_csrf', csrfToken);
            formData.append('plan_id', plan.id);
            formData.append('currency', 'USD');

            fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/paypal/create-order') ?>', {
                method: 'POST',
                body: formData,
                headers: { 
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.success) {
                    btnPayPayPal.disabled = false;
                    btnPayPayPal.innerHTML = '<i class="bi bi-paypal me-1"></i> Pay with PayPal';
                    showAlert('danger', data.error || data.message || 'Failed to initialize PayPal order.');
                    return;
                }

                const orderId = data.order_id;
                const intentId = data.intent_id;
                const approvalUrl = data.approval_url;

                if (approvalUrl) {
                    showLoadingState('Connecting to PayPal', 'Completing payment authorization...', 'Processing');

                    if (approvalUrl.includes('PAYPAL_SIM_')) {
                        // Immediate capture for simulation
                        executePayPalCapture(orderId, intentId);
                    } else {
                        // Open PayPal checkout window
                        const paypalWindow = window.open(approvalUrl, 'PayPalCheckout', 'width=500,height=700');
                        startPolling(intentId);

                        // Monitor window closure
                        const checkWindow = setInterval(() => {
                            if (!paypalWindow || paypalWindow.closed) {
                                clearInterval(checkWindow);
                                executePayPalCapture(orderId, intentId);
                            }
                        }, 2000);
                    }
                } else {
                    executePayPalCapture(orderId, intentId);
                }
            })
            .catch(() => {
                btnPayPayPal.disabled = false;
                btnPayPayPal.innerHTML = '<i class="bi bi-paypal me-1"></i> Pay with PayPal';
                showAlert('danger', 'Network error connecting to PayPal. Please try again.');
            });
        });
    }

    function executePayPalCapture(orderId, intentId) {
        showLoadingState('Verifying Payment', 'Confirming payment capture...', 'Verifying');

        const capData = new FormData();
        capData.append('_csrf', csrfToken);
        capData.append('order_id', orderId);
        capData.append('intent_id', intentId);

        fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/paypal/capture-order') ?>', {
            method: 'POST',
            body: capData,
            headers: { 
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-Token': csrfToken
            }
        })
        .then(res => res.json())
        .then(res => {
            if (res.success && res.status === 'completed') {
                showSuccessState('Payment verified! Your Benchero subscription is now active.');
                setTimeout(() => window.location.reload(), 1500);
            } else {
                showErrorState(res.message || 'Payment could not be completed.');
            }
        })
        .catch(() => {
            showErrorState('Network error verifying PayPal payment.');
        });
    }

    function startPolling(intentId) {
        pollCount = 0;
        if (pollInterval) clearInterval(pollInterval);

        pollInterval = setInterval(function () {
            pollCount++;
            if (pollCount > maxPolls) {
                clearInterval(pollInterval);
                showErrorState('Payment confirmation timed out. If you authorized payment, your subscription will activate automatically shortly.');
                return;
            }

            fetch('<?= url('/o/' . urlencode($tenant['slug']) . '/billing/payment-intent/') ?>' + intentId + '/status', {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'completed') {
                    clearInterval(pollInterval);
                    showSuccessState('Payment verified! Your Benchero subscription is now active.');
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
});
</script>
