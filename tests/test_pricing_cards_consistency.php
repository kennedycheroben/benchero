<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\PricingConfig;
use Benchero\Services\PaymentService;
use Benchero\Services\SubscriptionService;
use Benchero\Services\EntitlementService;
use Benchero\Core\Ulid;

class PricingCardsConsistencyTest
{
    private \PDO $pdo;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;
    private EntitlementService $entitlementService;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->paymentService = new PaymentService();
        $this->subscriptionService = new SubscriptionService();
        $this->entitlementService = new EntitlementService($this->pdo, $this->subscriptionService);
    }

    private function assert($condition, string $description): void
    {
        if ($condition) {
            echo " [PASS] {$description}\n";
            $this->passed++;
        } else {
            echo " [FAIL] {$description}\n";
            $this->failed++;
        }
    }

    public function run(): void
    {
        echo "========================================================\n";
        echo " BENCHERO PRICING CARDS & SUBSCRIPTION CONSISTENCY TEST\n";
        echo "========================================================\n";

        // 1. Authoritative Database Plans Verification
        $stmt = $this->pdo->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price_kes ASC");
        $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $this->assert(count($plans) >= 5, "Database contains all 5 commercial plans");

        $planMap = [];
        foreach ($plans as $p) {
            $planMap[$p['slug']] = $p;
            $planMap['id_' . $p['id']] = $p;
        }

        // Test Free Trial
        $trial = $planMap['free-trial'] ?? null;
        $this->assert(!empty($trial) && (int)$trial['id'] === 1, "Free Trial exists with ID 1");
        $this->assert((float)$trial['price_kes'] === 0.0, "Free Trial price is 0.00 KES");
        $this->assert($trial['billing_interval'] === 'trial', "Free Trial interval is 'trial'");

        // Test Standard Monthly
        $stdM = $planMap['standard-monthly'] ?? null;
        $this->assert(!empty($stdM) && (int)$stdM['id'] === 2, "Standard Monthly exists with ID 2");
        $this->assert((float)$stdM['price_kes'] === 1000.0, "Standard Monthly price is 1,000.00 KES");
        $this->assert($stdM['billing_interval'] === 'monthly', "Standard Monthly interval is 'monthly'");

        // Test Benchero Pro Monthly (Plan 5)
        $proM = $planMap['pro-monthly'] ?? null;
        $this->assert(!empty($proM) && (int)$proM['id'] === 5, "Benchero Pro Monthly exists with ID 5 and slug 'pro-monthly'");
        $this->assert((float)$proM['price_kes'] === 2500.0, "Benchero Pro Monthly price is exactly 2,500.00 KES");
        $this->assert($proM['billing_interval'] === 'monthly', "Benchero Pro Monthly interval is 'monthly'");

        // Test Standard Yearly
        $stdY = $planMap['standard-yearly'] ?? null;
        $this->assert(!empty($stdY) && (int)$stdY['id'] === 3, "Standard Yearly exists with ID 3");
        $this->assert((float)$stdY['price_kes'] === 10000.0, "Standard Yearly price is 10,000.00 KES");
        $this->assert($stdY['billing_interval'] === 'yearly', "Standard Yearly interval is 'yearly'");

        // Test Benchero Pro Yearly (Plan 4)
        $proY = $planMap['benchero-pro'] ?? null;
        $this->assert(!empty($proY) && (int)$proY['id'] === 4, "Benchero Pro Yearly exists with ID 4 and slug 'benchero-pro'");
        $this->assert((float)$proY['price_kes'] === 25000.0, "Benchero Pro Yearly price is exactly 25,000.00 KES");
        $this->assert($proY['billing_interval'] === 'yearly', "Benchero Pro Yearly interval is 'yearly'");

        // 2. PricingConfig Synchronization
        $this->assert(isset(PricingConfig::PLANS['free-trial']), "PricingConfig defines 'free-trial'");
        $this->assert(isset(PricingConfig::PLANS['standard-monthly']), "PricingConfig defines 'standard-monthly'");
        $this->assert(isset(PricingConfig::PLANS['pro-monthly']), "PricingConfig defines 'pro-monthly'");
        $this->assert(isset(PricingConfig::PLANS['standard-yearly']), "PricingConfig defines 'standard-yearly'");
        $this->assert(isset(PricingConfig::PLANS['benchero-pro']), "PricingConfig defines 'benchero-pro'");

        $this->assert((float)PricingConfig::PLANS['pro-monthly']['price_monthly_kes'] === 2500.0, "PricingConfig pro-monthly is 2,500 KES");
        $this->assert((float)PricingConfig::PLANS['benchero-pro']['price_yearly_kes'] === 25000.0, "PricingConfig benchero-pro is 25,000 KES");

        // 3. Payment Intent End-to-End Simulation
        $testOrgId = Ulid::generate();
        $this->pdo->exec("INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at) VALUES ('{$testOrgId}', 'Test Pricing Org', 'test-pricing-org-{$testOrgId}', 'KE', 'Africa/Nairobi', NOW(), NOW())");

        // Intent for Plan 5 (Pro Monthly - 2,500 KES)
        $intent5 = $this->paymentService->createPaymentIntent($testOrgId, null, 5, 'mpesa', ['phone' => '0712345678']);
        $this->assert($intent5['success'] === true, "Payment intent for Plan 5 (Pro Monthly) created successfully");
        $this->assert((float)$intent5['amount'] === 2500.0, "Plan 5 charged amount is 2,500.00 KES");

        // Intent for Plan 4 (Pro Yearly - 25,000 KES)
        $intent4 = $this->paymentService->createPaymentIntent($testOrgId, null, 4, 'mpesa', ['phone' => '0712345678']);
        $this->assert($intent4['success'] === true, "Payment intent for Plan 4 (Pro Yearly) created successfully");
        $this->assert((float)$intent4['amount'] === 25000.0, "Plan 4 charged amount is 25,000.00 KES");

        // Monthly and yearly plans cannot accidentally be swapped
        $this->assert((float)$intent5['amount'] !== (float)$intent4['amount'], "Plan 5 (Monthly 2,500) and Plan 4 (Yearly 25,000) amounts are distinct and never swapped");

        // 4. Subscription Activation & Entitlements for Plan 5
        $activated5 = $this->subscriptionService->activateSubscription($testOrgId, 5, 'TEST_PAY_REF_5');
        $this->assert($activated5 === true, "Subscription activated for Plan 5 (Benchero Pro Monthly)");

        $status5 = $this->subscriptionService->getSubscriptionStatus($testOrgId);
        $this->assert($status5['status'] === SubscriptionService::STATUS_ACTIVE, "Subscription status is ACTIVE for Plan 5");
        $this->assert((int)$status5['plan_id'] === 5, "Subscription plan_id is 5");
        $this->assert($status5['billing_interval'] === 'monthly', "Subscription interval is 'monthly'");

        // Verify Pro Entitlements are fully granted for Plan 5
        $this->assert($this->entitlementService->hasCapability($testOrgId, EntitlementService::CAP_CUSTOM_DOMAIN), "Plan 5 has custom_domain entitlement");
        $this->assert($this->entitlementService->hasCapability($testOrgId, EntitlementService::CAP_VIDEO_UPLOADS), "Plan 5 has video_uploads entitlement");
        $this->assert($this->entitlementService->hasCapability($testOrgId, EntitlementService::CAP_DIGITAL_CLUB_CARD), "Plan 5 has digital_club_card entitlement");
        $this->assert($this->entitlementService->hasCapability($testOrgId, EntitlementService::CAP_DATA_EXPORT), "Plan 5 has data_export entitlement");

        // 5. Rendered HTML Verification of /pricing
        $request = new \Benchero\Core\Http\Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/pricing'], [], []);
        $homeController = new \Benchero\Controllers\HomeController();
        $response = $homeController->pricing($request);
        $html = $response->getContent();

        $this->assert(str_contains($html, 'pricing-scroll-container'), "Rendered HTML contains .pricing-scroll-container");
        $this->assert(str_contains($html, 'pricing-track'), "Rendered HTML contains .pricing-track");

        // Verify all 5 cards exist in the rendered HTML
        $this->assert(str_contains($html, 'Free Trial'), "Card 1 'Free Trial' present");
        $this->assert(str_contains($html, 'Standard Monthly'), "Card 2 'Standard Monthly' present");
        $this->assert(str_contains($html, 'Benchero Pro Monthly'), "Card 3 'Benchero Pro Monthly' present");
        $this->assert(str_contains($html, 'Standard Yearly'), "Card 4 'Standard Yearly' present");
        $this->assert(str_contains($html, 'Benchero Pro Yearly'), "Card 5 'Benchero Pro Yearly' present");

        // Verify exact price strings
        $this->assert(str_contains($html, 'KSh 0'), "Price KSh 0 present");
        $this->assert(str_contains($html, 'KSh 1,000'), "Price KSh 1,000 present");
        $this->assert(str_contains($html, 'KSh 2,500'), "Price KSh 2,500 present");
        $this->assert(str_contains($html, 'KSh 10,000'), "Price KSh 10,000 present");
        $this->assert(str_contains($html, 'KSh 25,000'), "Price KSh 25,000 present");

        // Verify savings calculations & rendered badges
        $stdSavingsKES = (12 * (float)$stdM['price_kes']) - (float)$stdY['price_kes'];
        $this->assert((float)$stdSavingsKES === 2000.0, "Standard Yearly savings is exactly 2,000.00 KES (12 * 1,000 - 10,000)");
        $proSavingsKES = (12 * (float)$proM['price_kes']) - (float)$proY['price_kes'];
        $this->assert((float)$proSavingsKES === 5000.0, "Pro Yearly savings is exactly 5,000.00 KES (12 * 2,500 - 25,000)");

        $this->assert(str_contains($html, 'Save KSh 2,000'), "Standard Yearly savings badge 'Save KSh 2,000' present in HTML");
        $this->assert(str_contains($html, 'Save KSh 5,000'), "Pro Yearly savings badge 'Save KSh 5,000' present in HTML");

        // Verify CTA links
        $this->assert(str_contains($html, '/register?plan=free-trial'), "CTA link for free-trial present");
        $this->assert(str_contains($html, '/register?plan=standard-monthly'), "CTA link for standard-monthly present");
        $this->assert(str_contains($html, '/register?plan=pro-monthly'), "CTA link for pro-monthly present");
        $this->assert(str_contains($html, '/register?plan=standard-yearly'), "CTA link for standard-yearly present");
        $this->assert(str_contains($html, '/register?plan=benchero-pro'), "CTA link for benchero-pro present");

        // Verify independent View more buttons and target drawers
        $this->assert(str_contains($html, 'id="proMonthlyToggleBtn"'), "Independent Pro Monthly toggle button present");
        $this->assert(str_contains($html, 'id="proMonthlyFeaturesExtra"'), "Independent Pro Monthly features extra container present");
        $this->assert(str_contains($html, 'id="proYearlyToggleBtn"'), "Independent Pro Yearly toggle button present");
        $this->assert(str_contains($html, 'id="proYearlyFeaturesExtra"'), "Independent Pro Yearly features extra container present");

        // Verify exact ordering in HTML: Free Trial < Standard Monthly < Benchero Pro Monthly < Standard Yearly < Benchero Pro Yearly
        $posTrial = strpos($html, 'Free Trial');
        $posStdM  = strpos($html, 'Standard Monthly');
        $posProM  = strpos($html, 'Benchero Pro Monthly');
        $posStdY  = strpos($html, 'Standard Yearly');
        $posProY  = strpos($html, 'Benchero Pro Yearly');

        $this->assert($posTrial < $posStdM && $posStdM < $posProM && $posProM < $posStdY && $posStdY < $posProY, "All 5 cards appear in the exact required sequence: Free Trial -> Standard Monthly -> Pro Monthly -> Standard Yearly -> Pro Yearly");

        // Clean up test org
        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id = '{$testOrgId}'");
        $this->pdo->exec("DELETE FROM subscriptions WHERE organization_id = '{$testOrgId}'");
        $this->pdo->exec("DELETE FROM organizations WHERE id = '{$testOrgId}'");

        echo "========================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$suite = new PricingCardsConsistencyTest();
$suite->run();
