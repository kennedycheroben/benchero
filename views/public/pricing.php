<?php $this->layout('layout', ['title' => $title]) ?>

<?php
use Benchero\Core\PricingConfig;

$activeCurrency = strtoupper($activeCurrency ?? $_SESSION['currency'] ?? $_COOKIE['benchero_currency'] ?? 'KES');
if (!in_array($activeCurrency, ['KES', 'USD'], true)) {
    $activeCurrency = 'KES';
}

// Authoritative plan resolution from passed $plans
$plansBySlug = [];
$plansById = [];
if (!empty($plans)) {
    foreach ($plans as $p) {
        $plansBySlug[$p['slug']] = $p;
        $plansById[(int)$p['id']] = $p;
    }
}

// 1. Free Trial (Plan 1)
$trialPlan = $plansBySlug['free-trial'] ?? $plansById[1] ?? [
    'id' => 1,
    'name' => 'Free Trial',
    'slug' => 'free-trial',
    'price_kes' => 0.00,
    'billing_interval' => 'trial',
    'features' => json_encode(['team_limit' => 2, 'player_limit' => 25]),
];

// 2. Standard Monthly (Plan 2)
$stdMonthlyPlan = $plansBySlug['standard-monthly'] ?? $plansById[2] ?? [
    'id' => 2,
    'name' => 'Standard Monthly',
    'slug' => 'standard-monthly',
    'price_kes' => 1000.00,
    'billing_interval' => 'monthly',
    'features' => json_encode(['team_limit' => 10, 'player_limit' => 100]),
];

// 3. Benchero Pro Monthly (Plan 5)
$proMonthlyPlan = $plansBySlug['pro-monthly'] ?? $plansById[5] ?? [
    'id' => 5,
    'name' => 'Benchero Pro Monthly',
    'slug' => 'pro-monthly',
    'price_kes' => 2500.00,
    'billing_interval' => 'monthly',
    'features' => json_encode(['team_limit' => 100, 'player_limit' => 5000]),
];

// 4. Standard Yearly (Plan 3)
$stdYearlyPlan = $plansBySlug['standard-yearly'] ?? $plansById[3] ?? [
    'id' => 3,
    'name' => 'Standard Yearly',
    'slug' => 'standard-yearly',
    'price_kes' => 10000.00,
    'billing_interval' => 'yearly',
    'features' => json_encode(['team_limit' => 25, 'player_limit' => 500]),
];

// 5. Benchero Pro Yearly (Plan 4)
$proYearlyPlan = $plansBySlug['benchero-pro'] ?? $plansById[4] ?? [
    'id' => 4,
    'name' => 'Benchero Pro Yearly',
    'slug' => 'benchero-pro',
    'price_kes' => 20000.00,
    'billing_interval' => 'yearly',
    'features' => json_encode(['team_limit' => 100, 'player_limit' => 5000]),
];

// Decode features
$trialFeatures = json_decode($trialPlan['features'] ?? '{}', true);
$stdMFeatures = json_decode($stdMonthlyPlan['features'] ?? '{}', true);
$proMFeatures = json_decode($proMonthlyPlan['features'] ?? '{}', true);
$stdYFeatures = json_decode($stdYearlyPlan['features'] ?? '{}', true);
$proYFeatures = json_decode($proYearlyPlan['features'] ?? '{}', true);

// Shared Pro Feature Sets
$proCoreFeatures = [
    'Unlimited Teams Capacity',
    'Unlimited Players Capacity',
    'Multi-Sport Operations',
    'Public Club Website',
];

$proExtraFeatures = [
    'Custom Domain (www.myclub.co.ke)',
    'Controlled Video Uploads (2 GB)',
    'Club Media Center (5 GB)',
    'Mobile Digital Club Card',
    'Instant SVG QR Codes',
    'Advanced Stats & Competitions',
    'Advanced News & Social OG Data',
    'Data Exports (CSV & JSON)',
    'Additional Admins & Priority Support',
    'Remove Benchero Branding',
];

// Resolve Authoritative International USD Pricing
$intlTrial = PricingConfig::getInternationalPrice(1, 'USD');
$intlStdM = PricingConfig::getInternationalPrice(2, 'USD');
$intlStdY = PricingConfig::getInternationalPrice(3, 'USD');
$intlProM = PricingConfig::getInternationalPrice(5, 'USD');
$intlProY = PricingConfig::getInternationalPrice(4, 'USD');

// 5 Canonical Cards in Required Logical Sequence:
// FREE TRIAL -> STANDARD MONTHLY -> BENCHERO PRO MONTHLY -> STANDARD YEARLY -> BENCHERO PRO YEARLY
$pricingCards = [
    // 1. Free Trial
    [
        'plan' => $trialPlan,
        'badge' => '14-Day Free Access',
        'badge_class' => 'badge-trial',
        'badge_icon' => null,
        'name' => 'Free Trial',
        'desc' => 'Test core features risk-free with zero commitment or card.',
        'price_kes' => 'KSh 0',
        'price_usd' => '$0',
        'interval' => '/ trial',
        'savings_kes' => null,
        'savings_usd' => null,
        'savings_class' => null,
        'cta_text' => 'Start Free Trial',
        'cta_class' => 'btn-trial',
        'is_pro' => false,
        'is_yearly' => false,
        'card_class' => 'card-trial',
        'primary_features' => [
            PricingConfig::formatTeamLimit($trialFeatures['team_limit'] ?? 2) . ' Capacity',
            PricingConfig::formatPlayerLimit($trialFeatures['player_limit'] ?? 25) . ' Roster Capacity',
            'Multi-Sport Operations',
            'Public Club Website',
        ],
        'extra_features' => [],
        'toggle_id' => null,
        'extra_id' => null,
        'cta_url_kes' => url('/register?plan=' . urlencode($trialPlan['slug']) . '&currency=KES'),
        'cta_url_usd' => url('/register?plan=' . urlencode($trialPlan['slug']) . '&currency=USD'),
    ],
    // 2. Standard Monthly
    [
        'plan' => $stdMonthlyPlan,
        'badge' => 'Standard Monthly',
        'badge_class' => 'badge-standard',
        'badge_icon' => null,
        'name' => 'Standard Monthly',
        'desc' => 'Essential team management for developing sports clubs.',
        'price_kes' => 'KSh 1,000',
        'price_usd' => '$' . number_format($intlStdM['charged_amount'], 2),
        'interval' => '/ month',
        'savings_kes' => null,
        'savings_usd' => null,
        'savings_class' => null,
        'cta_text' => 'Choose Standard Monthly',
        'cta_class' => 'btn-standard-monthly',
        'is_pro' => false,
        'is_yearly' => false,
        'card_class' => 'card-standard-monthly',
        'primary_features' => [
            PricingConfig::formatTeamLimit($stdMFeatures['team_limit'] ?? 10) . ' Capacity',
            PricingConfig::formatPlayerLimit($stdMFeatures['player_limit'] ?? 100) . ' Roster Capacity',
            'Multi-Sport Operations',
            'Public Club Website',
        ],
        'extra_features' => [],
        'toggle_id' => null,
        'extra_id' => null,
        'cta_url_kes' => url('/register?plan=' . urlencode($stdMonthlyPlan['slug']) . '&currency=KES'),
        'cta_url_usd' => url('/register?plan=' . urlencode($stdMonthlyPlan['slug']) . '&currency=USD'),
    ],
    // 3. Benchero Pro Monthly (KSh 2,500 / $20.00)
    [
        'plan' => $proMonthlyPlan,
        'badge' => 'Pro Monthly',
        'badge_class' => 'badge-pro',
        'badge_icon' => 'bi-star-fill text-warning me-1',
        'name' => 'Benchero Pro Monthly',
        'desc' => 'Complete digital presence on flexible monthly terms.',
        'price_kes' => 'KSh 2,500',
        'price_usd' => '$' . number_format($intlProM['charged_amount'], 2),
        'interval' => '/ month',
        'savings_kes' => null,
        'savings_usd' => null,
        'savings_class' => null,
        'cta_text' => 'Choose Pro Monthly',
        'cta_class' => 'btn-pro',
        'is_pro' => true,
        'is_yearly' => false,
        'card_class' => 'card-pro card-pro-monthly',
        'primary_features' => $proCoreFeatures,
        'extra_features' => $proExtraFeatures,
        'toggle_id' => 'proMonthlyToggleBtn',
        'extra_id' => 'proMonthlyFeaturesExtra',
        'cta_url_kes' => url('/register?plan=' . urlencode($proMonthlyPlan['slug']) . '&currency=KES'),
        'cta_url_usd' => url('/register?plan=' . urlencode($proMonthlyPlan['slug']) . '&currency=USD'),
    ],
    // 4. Standard Yearly
    [
        'plan' => $stdYearlyPlan,
        'badge' => 'Annual Value',
        'badge_class' => 'badge-yearly',
        'badge_icon' => 'bi-shield-check me-1',
        'name' => 'Standard Yearly',
        'desc' => 'Expanded capacity & guaranteed annual savings for active clubs.',
        'price_kes' => 'KSh 10,000',
        'price_usd' => '$' . number_format($intlStdY['charged_amount'], 2),
        'interval' => '/ year',
        'savings_kes' => 'Save KSh 2,000 / year',
        'savings_usd' => 'Save $' . number_format(($intlStdM['charged_amount'] * 12) - $intlStdY['charged_amount'], 0) . ' / year',
        'savings_class' => 'savings-badge-yearly',
        'cta_text' => 'Choose Standard Yearly',
        'cta_class' => 'btn-standard-yearly',
        'is_pro' => false,
        'is_yearly' => true,
        'card_class' => 'card-standard-yearly',
        'primary_features' => [
            PricingConfig::formatTeamLimit($stdYFeatures['team_limit'] ?? 25) . ' Capacity',
            PricingConfig::formatPlayerLimit($stdYFeatures['player_limit'] ?? 500) . ' Roster Capacity',
            'Multi-Sport Operations',
            'Public Club Website',
        ],
        'extra_features' => [],
        'toggle_id' => null,
        'extra_id' => null,
        'cta_url_kes' => url('/register?plan=' . urlencode($stdYearlyPlan['slug']) . '&currency=KES'),
        'cta_url_usd' => url('/register?plan=' . urlencode($stdYearlyPlan['slug']) . '&currency=USD'),
    ],
    // 5. Benchero Pro Yearly
    [
        'plan' => $proYearlyPlan,
        'badge' => '★ BENCHERO PRO',
        'badge_class' => 'badge-pro',
        'badge_icon' => 'bi-star-fill text-warning me-1',
        'name' => 'Benchero Pro Yearly',
        'desc' => 'Complete digital presence with maximum annual savings.',
        'price_kes' => 'KSh 20,000',
        'price_usd' => '$' . number_format($intlProY['charged_amount'], 2),
        'interval' => '/ year',
        'savings_kes' => 'Save KSh 10,000 / year',
        'savings_usd' => 'Save $' . number_format(($intlProM['charged_amount'] * 12) - $intlProY['charged_amount'], 0) . ' / year',
        'savings_class' => 'savings-badge-pro',
        'cta_text' => 'Choose Pro Yearly',
        'cta_class' => 'btn-pro',
        'is_pro' => true,
        'is_yearly' => true,
        'card_class' => 'card-pro card-pro-yearly',
        'primary_features' => $proCoreFeatures,
        'extra_features' => $proExtraFeatures,
        'toggle_id' => 'proYearlyToggleBtn',
        'extra_id' => 'proYearlyFeaturesExtra',
        'cta_url_kes' => url('/register?plan=' . urlencode($proYearlyPlan['slug']) . '&currency=KES'),
        'cta_url_usd' => url('/register?plan=' . urlencode($proYearlyPlan['slug']) . '&currency=USD'),
    ]
];
?>

<style>
/* ==========================================================================
   Benchero SaaS Pricing System — Compact 5-Card Layout & Currency Selector
   ========================================================================== */

.pricing-section {
    background-color: var(--benchero-bg, #f8fafc);
    position: relative;
    overflow-x: clip;
}

/* Prominent Dual-Currency Selector */
.currency-selector-box {
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
    background: #ffffff;
    display: inline-flex;
}

.currency-toggle-btn {
    border: 1px solid transparent;
    color: #475569;
    background: transparent;
    transition: all 0.2s ease;
    font-size: 0.84rem;
    cursor: pointer;
}

.currency-toggle-btn:hover {
    color: #0f172a;
    background: #f1f5f9;
}

.currency-toggle-btn.active {
    background: var(--benchero-primary, #0f172a) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(15, 23, 42, 0.2);
}

.currency-symbol-sub {
    font-size: 0.75rem;
    font-weight: 500;
    opacity: 0.85;
}

.fs-8 {
    font-size: 0.75rem;
}

/* Horizontal Scroll Wrapper & Container */
.pricing-scroll-wrapper {
    position: relative;
    width: 100%;
}

.pricing-scroll-container {
    display: flex;
    overflow-x: auto;
    overflow-y: visible;
    scroll-behavior: smooth;
    -webkit-overflow-scrolling: touch;
    padding: 0.5rem 0.25rem 1.25rem 0.25rem;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.pricing-scroll-container::-webkit-scrollbar {
    height: 6px;
}

.pricing-scroll-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 9999px;
}

.pricing-scroll-container::-webkit-scrollbar-thumb {
    background-color: #cbd5e1;
    border-radius: 9999px;
}

.pricing-scroll-container::-webkit-scrollbar-thumb:hover {
    background-color: #94a3b8;
}

/* Track layout */
.pricing-track {
    display: flex;
    gap: 1rem;
    align-items: stretch;
    min-width: min-content;
    margin: 0 auto;
}

/* Scroll Navigation Controls */
.pricing-scroll-nav {
    position: absolute;
    top: 42%;
    transform: translateY(-50%);
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.12);
    display: flex;
    align-items: center;
    justify-content: center;
    color: #1e293b;
    font-size: 1rem;
    cursor: pointer;
    z-index: 10;
    transition: all 0.2s ease;
}

.pricing-scroll-nav:hover {
    background: #0f172a;
    color: #ffffff;
    border-color: #0f172a;
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.2);
}

.pricing-scroll-nav:focus-visible {
    outline: 2px solid var(--benchero-accent, #2563eb);
    outline-offset: 2px;
}

.pricing-scroll-prev {
    left: -12px;
}

.pricing-scroll-next {
    right: -12px;
}

/* Edge Fade Hints */
.scroll-hint-left,
.scroll-hint-right {
    position: absolute;
    top: 0;
    bottom: 1.25rem;
    width: 28px;
    pointer-events: none;
    z-index: 5;
    opacity: 0;
    transition: opacity 0.25s ease;
}

.scroll-hint-left {
    left: 0;
    background: linear-gradient(to right, rgba(248, 250, 252, 0.95), transparent);
}

.scroll-hint-right {
    right: 0;
    background: linear-gradient(to left, rgba(248, 250, 252, 0.95), transparent);
}

.scroll-hint-visible {
    opacity: 1;
}

/* Individual Card Structure — Compact 50% Height */
.pricing-card {
    display: flex;
    flex-direction: column;
    width: 255px;
    min-width: 245px;
    max-width: 275px;
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 0.875rem;
    padding: 1.15rem 1rem;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    position: relative;
    box-sizing: border-box;
}

.pricing-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -4px rgba(15, 23, 42, 0.08), 0 4px 8px -2px rgba(15, 23, 42, 0.04);
    border-color: #cbd5e1;
}

/* Visual Hierarchy Tiers */
.card-trial {
    border-color: #e2e8f0;
}

.card-standard-monthly {
    border-color: #e2e8f0;
}

.card-standard-yearly {
    border-color: #86efac;
    background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
}

.card-standard-yearly:hover {
    border-color: #4ade80;
}

.card-pro {
    border: 2px solid var(--benchero-accent, #2563eb) !important;
    box-shadow: 0 6px 18px -4px rgba(37, 99, 235, 0.12), 0 4px 6px -2px rgba(37, 99, 235, 0.06);
}

.card-pro:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 24px -6px rgba(37, 99, 235, 0.18), 0 6px 10px -3px rgba(37, 99, 235, 0.08);
    border-color: var(--benchero-accent-hover, #1d4ed8) !important;
}

.card-pro-yearly {
    background: linear-gradient(180deg, #ffffff 0%, #eff6ff 100%);
}

/* Card Top: Anchor for CTA Baseline Alignment */
.pricing-card-top {
    display: flex;
    flex-direction: column;
}

.pricing-badge-wrapper {
    min-height: 24px;
    display: flex;
    align-items: center;
    margin-bottom: 0.4rem;
}

.pricing-badge {
    font-size: 0.68rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    padding: 0.25rem 0.6rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    line-height: 1;
    text-transform: uppercase;
}

.badge-trial {
    background: #f1f5f9;
    color: #475569;
    border: 1px solid #e2e8f0;
}

.badge-standard {
    background: #f1f5f9;
    color: #1e293b;
    border: 1px solid #e2e8f0;
}

.badge-yearly {
    background: #ecfdf5;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.badge-pro {
    background: var(--benchero-accent, #2563eb);
    color: #ffffff;
    border: 1px solid transparent;
}

.pricing-title {
    font-size: 1.05rem;
    font-weight: 800;
    color: var(--benchero-primary, #0f172a);
    margin-bottom: 0.2rem;
    min-height: 1.6rem;
    display: flex;
    align-items: center;
    letter-spacing: -0.015em;
    line-height: 1.25;
}

.pricing-desc {
    font-size: 0.78rem;
    line-height: 1.35;
    color: #64748b;
    margin-bottom: 0.45rem;
    min-height: 2.15rem;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

/* Price Box */
.pricing-price-box {
    display: flex;
    align-items: baseline;
    gap: 0.25rem;
    margin-bottom: 0.25rem;
    min-height: 2.3rem;
}

.pricing-amount {
    font-size: 1.75rem;
    font-weight: 800;
    color: var(--benchero-primary, #0f172a);
    letter-spacing: -0.035em;
    line-height: 1;
}

.pricing-interval {
    font-size: 0.78rem;
    font-weight: 600;
    color: #64748b;
}

/* Savings Badge */
.pricing-savings-wrapper {
    min-height: 24px;
    display: flex;
    align-items: center;
    margin-bottom: 0.6rem;
}

.savings-badge {
    font-size: 0.68rem;
    font-weight: 700;
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    display: inline-flex;
    align-items: center;
    line-height: 1.2;
}

.savings-badge-yearly {
    background: #ecfdf5;
    color: #15803d;
    border: 1px solid #bbf7d0;
}

.savings-badge-pro {
    background: #eff6ff;
    color: var(--benchero-accent, #2563eb);
    border: 1px solid #bfdbfe;
}

/* Primary CTA Button */
.pricing-cta-wrapper {
    margin-bottom: 0.65rem;
}

.pricing-cta-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    min-height: 38px;
    padding: 0.45rem 0.75rem;
    font-size: 0.84rem;
    font-weight: 700;
    border-radius: 0.5rem;
    text-decoration: none;
    text-align: center;
    transition: all 0.2s ease;
}

.pricing-cta-btn:focus-visible {
    outline: 2px solid var(--benchero-accent, #2563eb);
    outline-offset: 2px;
}

.btn-trial {
    background-color: transparent;
    color: var(--benchero-accent, #2563eb);
    border: 1.5px solid var(--benchero-accent, #2563eb);
}

.btn-trial:hover, .btn-trial:focus {
    background-color: #eff6ff;
    color: var(--benchero-accent-hover, #1d4ed8);
    border-color: var(--benchero-accent-hover, #1d4ed8);
}

.btn-standard-monthly {
    background-color: #0f172a;
    color: #ffffff;
    border: 1.5px solid #0f172a;
}

.btn-standard-monthly:hover, .btn-standard-monthly:focus {
    background-color: #1e293b;
    color: #ffffff;
    border-color: #1e293b;
}

.btn-standard-yearly {
    background-color: #16a34a;
    color: #ffffff;
    border: 1.5px solid #16a34a;
}

.btn-standard-yearly:hover, .btn-standard-yearly:focus {
    background-color: #15803d;
    color: #ffffff;
    border-color: #15803d;
}

.btn-pro {
    background-color: var(--benchero-accent, #2563eb);
    color: #ffffff;
    border: 1.5px solid var(--benchero-accent, #2563eb);
    box-shadow: 0 3px 8px rgba(37, 99, 235, 0.2);
}

.btn-pro:hover, .btn-pro:focus {
    background-color: var(--benchero-accent-hover, #1d4ed8);
    color: #ffffff;
    border-color: var(--benchero-accent-hover, #1d4ed8);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

/* Card Divider */
.pricing-divider {
    border: 0;
    height: 1px;
    background-color: #f1f5f9;
    margin: 0 0 0.65rem 0;
}

/* Features List — Compact & Readable */
.pricing-features-section {
    flex-grow: 1;
    display: flex;
    flex-direction: column;
}

.pricing-features-title {
    font-size: 0.68rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #94a3b8;
    margin-bottom: 0.4rem;
}

.pricing-features-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.pricing-feature-item {
    display: flex;
    align-items: flex-start;
    gap: 0.45rem;
    font-size: 0.8rem;
    line-height: 1.3;
    color: #334155;
}

.pricing-feature-item.pro-highlight {
    font-weight: 600;
    color: #0f172a;
}

.pricing-feature-icon {
    font-size: 0.85rem;
    line-height: 1.25;
    flex-shrink: 0;
}

.icon-success {
    color: #16a34a;
}

.icon-pro {
    color: var(--benchero-accent, #2563eb);
}

/* Expandable Feature Drawer */
.pricing-features-extra {
    max-height: 0;
    opacity: 0;
    overflow: hidden;
    transition: max-height 0.35s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.25s ease-in-out;
}

.pricing-features-extra.is-expanded {
    max-height: 600px;
    opacity: 1;
    margin-top: 0.35rem;
}

.pricing-features-toggle {
    font-size: 0.78rem;
    font-weight: 600;
    color: var(--benchero-accent, #2563eb);
    cursor: pointer;
    margin-top: 0.55rem;
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    background: none;
    border: none;
    padding: 0;
    text-decoration: none;
}

.pricing-features-toggle:hover, .pricing-features-toggle:focus {
    color: var(--benchero-accent-hover, #1d4ed8);
    text-decoration: underline;
}

.pricing-features-toggle:focus-visible {
    outline: 2px solid var(--benchero-accent, #2563eb);
    outline-offset: 2px;
    border-radius: 4px;
}

.pricing-features-toggle .toggle-icon {
    transition: transform 0.25s ease;
    font-size: 0.7rem;
}

.pricing-features-toggle[aria-expanded="true"] .toggle-icon {
    transform: rotate(180deg);
}

/* Reduced Motion */
@media (prefers-reduced-motion: reduce) {
    .pricing-card,
    .pricing-card:hover,
    .pricing-cta-btn,
    .pricing-features-extra,
    .pricing-features-toggle .toggle-icon,
    .pricing-scroll-container,
    .currency-toggle-btn {
        transition: none !important;
        transform: none !important;
    }
}

/* Responsive & Mobile Touch */
@media (max-width: 768px) {
    .pricing-scroll-nav {
        display: none !important;
    }
}

@media (max-width: 576px) {
    .pricing-scroll-container {
        scroll-snap-type: x mandatory;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    .pricing-card {
        scroll-snap-align: center;
        flex: 0 0 84vw;
        min-width: 260px;
        max-width: 320px;
    }
}
</style>

<!-- Hero Header Section -->
<section class="py-4 py-lg-5 bg-white border-bottom text-center">
    <div class="container py-lg-2">
        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-1 rounded-pill mb-2">Benchero Commercial Plans</span>
        <h1 class="display-6 fw-extrabold mb-2 text-dark">Simple & Transparent Pricing for Every Club</h1>
        <p class="text-muted max-w-2xl mx-auto mb-0 small">Empower your sports organization with complete team, player, fixture, media and public digital presence.</p>
    </div>
</section>

<!-- 5-Card SaaS Pricing Section with Dual-Currency Selector & Horizontal Scroll -->
<section class="py-4 py-lg-5 pricing-section">
    <div class="container-fluid px-3 px-xl-5">

        <!-- Prominent Dual-Currency Selector -->
        <div class="text-center mb-4 mb-lg-5">
            <div class="currency-selector-box p-1 rounded-pill" id="currencySelector" role="radiogroup" aria-label="Select billing currency">
                <span class="text-muted small fw-semibold px-3 py-1.5 d-none d-sm-inline align-self-center">
                    <i class="bi bi-globe2 me-1"></i>Currency:
                </span>
                <button type="button" 
                        class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold currency-toggle-btn <?= $activeCurrency === 'KES' ? 'active' : '' ?>" 
                        id="currencyBtnKES" 
                        data-currency="KES" 
                        role="radio" 
                        aria-checked="<?= $activeCurrency === 'KES' ? 'true' : 'false' ?>">
                    KES <span class="currency-symbol-sub">KSh</span>
                </button>
                <button type="button" 
                        class="btn btn-sm rounded-pill px-3 py-1.5 fw-bold currency-toggle-btn <?= $activeCurrency === 'USD' ? 'active' : '' ?>" 
                        id="currencyBtnUSD" 
                        data-currency="USD" 
                        role="radio" 
                        aria-checked="<?= $activeCurrency === 'USD' ? 'true' : 'false' ?>">
                    USD <span class="currency-symbol-sub">$</span>
                </button>
            </div>
            
            <div class="currency-notice mt-2 text-muted fs-8" id="currencyNotice">
                <i class="bi bi-shield-check text-success me-1"></i>
                <span id="currencyNoticeText">
                    <?= $activeCurrency === 'USD' 
                        ? 'Prices shown in USD ($). International billing supported via PayPal & Credit/Debit cards.' 
                        : 'Prices shown in KES (KSh). Kenyan billing supported via M-Pesa STK Push.' ?>
                </span>
            </div>
        </div>

        <div class="pricing-scroll-wrapper">
            
            <!-- Left & Right Fade Indicators -->
            <div class="scroll-hint-left" id="scrollHintLeft"></div>
            <div class="scroll-hint-right" id="scrollHintRight"></div>

            <!-- Left & Right Arrow Nav Controls -->
            <button type="button" class="pricing-scroll-nav pricing-scroll-prev" id="pricingScrollPrev" aria-label="Scroll left to see earlier plans" style="display: none;">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="pricing-scroll-nav pricing-scroll-next" id="pricingScrollNext" aria-label="Scroll right to see more plans">
                <i class="bi bi-chevron-right"></i>
            </button>

            <!-- Scrollable Track Container -->
            <div class="pricing-scroll-container" id="pricingScrollContainer" tabindex="0" role="region" aria-label="Pricing plans comparison table">
                <div class="pricing-track">
                    <?php foreach ($pricingCards as $index => $card): ?>
                        <?php
                            $isUsdActive = ($activeCurrency === 'USD');
                            $currentPrice = $isUsdActive ? $card['price_usd'] : $card['price_kes'];
                            $currentSavings = $isUsdActive ? $card['savings_usd'] : $card['savings_kes'];
                            $currentCtaUrl = $isUsdActive ? $card['cta_url_usd'] : $card['cta_url_kes'];
                        ?>
                        <div class="pricing-card <?= htmlspecialchars($card['card_class']) ?>"
                             data-plan-slug="<?= htmlspecialchars($card['plan']['slug']) ?>"
                             data-price-kes="<?= htmlspecialchars($card['price_kes']) ?>"
                             data-price-usd="<?= htmlspecialchars($card['price_usd']) ?>"
                             data-interval="<?= htmlspecialchars($card['interval']) ?>"
                             data-savings-kes="<?= htmlspecialchars($card['savings_kes'] ?? '') ?>"
                             data-savings-usd="<?= htmlspecialchars($card['savings_usd'] ?? '') ?>"
                             data-cta-kes="<?= htmlspecialchars($card['cta_url_kes']) ?>"
                             data-cta-usd="<?= htmlspecialchars($card['cta_url_usd']) ?>">
                            
                            <!-- Top Block: Alignment Anchor -->
                            <div class="pricing-card-top">
                                <!-- 1. Plan Badge / Label -->
                                <div class="pricing-badge-wrapper">
                                    <span class="pricing-badge <?= htmlspecialchars($card['badge_class']) ?>">
                                        <?php if ($card['badge_icon']): ?>
                                            <i class="bi <?= htmlspecialchars($card['badge_icon']) ?>"></i>
                                        <?php endif; ?>
                                        <?= htmlspecialchars($card['badge']) ?>
                                    </span>
                                </div>

                                <!-- 2. Plan Name -->
                                <h2 class="pricing-title"><?= htmlspecialchars($card['name']) ?></h2>

                                <!-- 3. Short Description -->
                                <p class="pricing-desc"><?= htmlspecialchars($card['desc']) ?></p>

                                <!-- 4. Price & Billing Period -->
                                <div class="pricing-price-box">
                                    <span class="pricing-amount"><?= htmlspecialchars($currentPrice) ?></span>
                                    <span class="pricing-interval"><?= htmlspecialchars($card['interval']) ?></span>
                                </div>

                                <!-- 5. Savings Badge (Alignment Placeholder) -->
                                <div class="pricing-savings-wrapper">
                                    <span class="savings-badge <?= htmlspecialchars($card['savings_class'] ?? '') ?> <?= empty($currentSavings) ? 'd-none' : '' ?>">
                                        <?php if ($card['is_yearly'] && !$card['is_pro']): ?>
                                            <i class="bi bi-tag-fill me-1"></i>
                                        <?php endif; ?>
                                        <span class="savings-badge-text"><?= htmlspecialchars($currentSavings ?? '') ?></span>
                                    </span>
                                </div>

                                <!-- 6. Primary CTA Button -->
                                <div class="pricing-cta-wrapper">
                                    <a href="<?= htmlspecialchars($currentCtaUrl) ?>" 
                                       class="pricing-cta-btn <?= htmlspecialchars($card['cta_class']) ?>">
                                        <?= htmlspecialchars($card['cta_text']) ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Divider -->
                            <hr class="pricing-divider">

                            <!-- 7. Feature List -->
                            <div class="pricing-features-section">
                                <div class="pricing-features-title">Features Included</div>
                                <ul class="pricing-features-list">
                                    <?php foreach ($card['primary_features'] as $feat): ?>
                                        <li class="pricing-feature-item <?= ($card['is_pro'] && (str_contains($feat, 'Unlimited') || str_contains($feat, 'Website'))) ? 'pro-highlight' : '' ?>">
                                            <i class="bi bi-check-circle-fill pricing-feature-icon <?= $card['is_pro'] ? 'icon-pro' : 'icon-success' ?>"></i>
                                            <span><?= htmlspecialchars($feat) ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>

                                <!-- Pro Expandable Feature Section -->
                                <?php if (!empty($card['extra_features']) && !empty($card['toggle_id']) && !empty($card['extra_id'])): ?>
                                    <div class="pricing-features-extra" id="<?= htmlspecialchars($card['extra_id']) ?>">
                                        <ul class="pricing-features-list">
                                            <?php foreach ($card['extra_features'] as $extraFeat): ?>
                                                <li class="pricing-feature-item pro-highlight">
                                                    <i class="bi bi-check-circle-fill pricing-feature-icon icon-pro"></i>
                                                    <span><?= htmlspecialchars($extraFeat) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>

                                    <button type="button" 
                                            class="pricing-features-toggle" 
                                            aria-expanded="false" 
                                            aria-controls="<?= htmlspecialchars($card['extra_id']) ?>" 
                                            id="<?= htmlspecialchars($card['toggle_id']) ?>"
                                            data-extra-id="<?= htmlspecialchars($card['extra_id']) ?>"
                                            data-count="<?= count($card['extra_features']) ?>">
                                        <span>View more (<?= count($card['extra_features']) ?> more)</span>
                                        <i class="bi bi-chevron-down toggle-icon"></i>
                                    </button>
                                <?php endif; ?>
                            </div>

                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Dual-Currency Toggle Management
    let currentCurrency = '<?= htmlspecialchars($activeCurrency) ?>';
    const btnKes = document.getElementById('currencyBtnKES');
    const btnUsd = document.getElementById('currencyBtnUSD');
    const noticeText = document.getElementById('currencyNoticeText');

    function updateCurrencyDisplay(newCurrency) {
        if (newCurrency !== 'KES' && newCurrency !== 'USD') return;
        currentCurrency = newCurrency;

        if (btnKes) {
            btnKes.classList.toggle('active', newCurrency === 'KES');
            btnKes.setAttribute('aria-checked', newCurrency === 'KES' ? 'true' : 'false');
        }
        if (btnUsd) {
            btnUsd.classList.toggle('active', newCurrency === 'USD');
            btnUsd.setAttribute('aria-checked', newCurrency === 'USD' ? 'true' : 'false');
        }

        if (noticeText) {
            noticeText.textContent = (newCurrency === 'USD')
                ? 'Prices shown in USD ($). International billing supported via PayPal & Credit/Debit cards.'
                : 'Prices shown in KES (KSh). Kenyan billing supported via M-Pesa STK Push.';
        }

        document.querySelectorAll('.pricing-card').forEach(function (card) {
            const amountEl = card.querySelector('.pricing-amount');
            const savingsBadge = card.querySelector('.savings-badge');
            const savingsTextEl = card.querySelector('.savings-badge-text');
            const ctaBtn = card.querySelector('.pricing-cta-btn');

            const price = (newCurrency === 'USD') ? card.dataset.priceUsd : card.dataset.priceKes;
            const savings = (newCurrency === 'USD') ? card.dataset.savingsUsd : card.dataset.savingsKes;
            const ctaUrl = (newCurrency === 'USD') ? card.dataset.ctaUsd : card.dataset.ctaKes;

            if (amountEl && price) {
                amountEl.textContent = price;
            }

            if (savingsBadge && savingsTextEl) {
                if (savings && savings.trim() !== '') {
                    savingsTextEl.textContent = savings;
                    savingsBadge.classList.remove('d-none');
                } else {
                    savingsBadge.classList.add('d-none');
                }
            }

            if (ctaBtn && ctaUrl) {
                ctaBtn.setAttribute('href', ctaUrl);
            }
        });

        // Store preference in localStorage
        try {
            localStorage.setItem('benchero_currency', newCurrency);
        } catch (e) {}

        // Persist preference in backend session/cookie
        const formData = new FormData();
        formData.append('currency', newCurrency);
        fetch('<?= url('/currency') ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        }).catch(function () {});
    }

    if (btnKes) {
        btnKes.addEventListener('click', function () {
            updateCurrencyDisplay('KES');
        });
    }

    if (btnUsd) {
        btnUsd.addEventListener('click', function () {
            updateCurrencyDisplay('USD');
        });
    }

    // 2. Horizontal Scroll Navigation and Hints
    const scrollContainer = document.getElementById('pricingScrollContainer');
    const prevBtn = document.getElementById('pricingScrollPrev');
    const nextBtn = document.getElementById('pricingScrollNext');
    const hintLeft = document.getElementById('scrollHintLeft');
    const hintRight = document.getElementById('scrollHintRight');

    function updateScrollState() {
        if (!scrollContainer) return;
        const scrollLeft = scrollContainer.scrollLeft;
        const maxScroll = scrollContainer.scrollWidth - scrollContainer.clientWidth;
        const isScrollable = maxScroll > 5;

        if (prevBtn) {
            prevBtn.style.display = (isScrollable && scrollLeft > 10) ? 'flex' : 'none';
        }
        if (nextBtn) {
            nextBtn.style.display = (isScrollable && scrollLeft < maxScroll - 10) ? 'flex' : 'none';
        }

        if (hintLeft) {
            hintLeft.classList.toggle('scroll-hint-visible', isScrollable && scrollLeft > 10);
        }
        if (hintRight) {
            hintRight.classList.toggle('scroll-hint-visible', isScrollable && scrollLeft < maxScroll - 10);
        }
    }

    if (scrollContainer) {
        scrollContainer.addEventListener('scroll', updateScrollState, { passive: true });
        window.addEventListener('resize', updateScrollState);
        setTimeout(updateScrollState, 100);
    }

    if (prevBtn && scrollContainer) {
        prevBtn.addEventListener('click', function () {
            scrollContainer.scrollBy({ left: -280, behavior: 'smooth' });
        });
    }

    if (nextBtn && scrollContainer) {
        nextBtn.addEventListener('click', function () {
            scrollContainer.scrollBy({ left: 280, behavior: 'smooth' });
        });
    }

    // 3. Independent Accessible View More / View Less Toggles for Pro Plans
    const toggleButtons = document.querySelectorAll('.pricing-features-toggle');
    toggleButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const extraId = this.getAttribute('data-extra-id') || this.getAttribute('aria-controls');
            const extraContainer = document.getElementById(extraId);
            if (!extraContainer) return;

            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const labelSpan = this.querySelector('span');
            const totalCount = this.getAttribute('data-count') || '10';

            if (isExpanded) {
                this.setAttribute('aria-expanded', 'false');
                extraContainer.classList.remove('is-expanded');
                if (labelSpan) {
                    labelSpan.textContent = 'View more (' + totalCount + ' more)';
                }
            } else {
                this.setAttribute('aria-expanded', 'true');
                extraContainer.classList.add('is-expanded');
                if (labelSpan) {
                    labelSpan.textContent = 'View less';
                }
            }
            setTimeout(updateScrollState, 360);
        });
    });
});
</script>
