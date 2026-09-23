<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\PricingConfig;
use Benchero\Core\Http\Request;
use Benchero\Controllers\HomeController;
use Benchero\Controllers\AuthController;
use Benchero\Controllers\Tenant\BillingController;
use Benchero\Services\PaymentService;
use Benchero\Services\SubscriptionService;
use Benchero\Core\Ulid;

register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && $err['type'] === E_ERROR) {
        echo "\n[FATAL ERROR] " . $err['message'] . " in " . $err['file'] . ":" . $err['line'] . "\n";
    }
});

class DualCurrencyPricingTest
{
    private \PDO $pdo;
    private PaymentService $paymentService;
    private int $passed = 0;
    private int $failed = 0;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->paymentService = new PaymentService();
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
        echo " BENCHERO DUAL-CURRENCY (KES & USD) SYSTEM TEST\n";
        echo "========================================================\n";

        // ----------------------------------------------------
        // 1. Authoritative Currency & Pricing Configuration
        // ----------------------------------------------------
        echo "\n--- 1. PricingConfig Authoritative Values ---\n";
        $this->assert(PricingConfig::isSupportedCurrency('KES'), "KES is a supported currency");
        $this->assert(PricingConfig::isSupportedCurrency('USD'), "USD is a supported currency");
        $this->assert(!PricingConfig::isSupportedCurrency('XYZ'), "Invalid currency XYZ is rejected");

        // Verify USD prices match exact specifications
        $usdTrial = PricingConfig::getInternationalPrice(1, 'USD')['charged_amount'];
        $this->assert((float)$usdTrial === 0.0, "Plan 1 (Free Trial) USD price is $0.00");

        $usdStdM = PricingConfig::getInternationalPrice(2, 'USD')['charged_amount'];
        $this->assert((float)$usdStdM === 8.0, "Plan 2 (Standard Monthly) USD price is $8.00");

        $usdStdY = PricingConfig::getInternationalPrice(3, 'USD')['charged_amount'];
        $this->assert((float)$usdStdY === 80.0, "Plan 3 (Standard Yearly) USD price is $80.00");

        $usdProM = PricingConfig::getInternationalPrice(5, 'USD')['charged_amount'];
        $this->assert((float)$usdProM === 20.0, "Plan 5 (Pro Monthly) USD price is $20.00");

        $usdProY = PricingConfig::getInternationalPrice(4, 'USD')['charged_amount'];
        $this->assert((float)$usdProY === 160.0, "Plan 4 (Pro Yearly) USD price is $160.00");

        // Verify savings calculation:
        // Std: 12 * $8 = $96. Yearly = $80. Savings = $16.
        $stdSavingsUSD = (12 * $usdStdM) - $usdStdY;
        $this->assert((float)$stdSavingsUSD === 16.0, "Standard Yearly saves $16.00/yr vs monthly");

        // Pro: 12 * $20 = $240. Yearly = $160. Savings = $80.
        $proSavingsUSD = (12 * $usdProM) - $usdProY;
        $this->assert((float)$proSavingsUSD === 80.0, "Pro Yearly saves $80.00/yr vs monthly");

        // ----------------------------------------------------
        // 2. HomeController::pricing Currency Resolution & HTML
        // ----------------------------------------------------
        echo "\n--- 2. HomeController Pricing View Resolution ---\n";
        $homeController = new HomeController();

        // 2a. Default currency should be KES
        $_SESSION = [];
        $_COOKIE = [];
        $requestDefault = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/pricing'], [], []);
        $respDefault = $homeController->pricing($requestDefault);
        $htmlDefault = $respDefault->getContent();

        $this->assert(str_contains($htmlDefault, 'id="currencySelector"'), "Currency selector element present in pricing HTML");
        $this->assert(str_contains($htmlDefault, 'data-currency="KES"'), "Default currency button has data-currency='KES'");
        $this->assert(str_contains($htmlDefault, 'data-currency="USD"'), "Currency button has data-currency='USD'");
        $this->assert(str_contains($htmlDefault, 'role="radiogroup"'), "Selector contains accessible role='radiogroup'");
        $this->assert(str_contains($htmlDefault, 'data-price-kes="KSh 1,000"'), "Plan 2 contains data-price-kes='KSh 1,000'");
        $this->assert(str_contains($htmlDefault, 'data-price-usd="$8.00"'), "Plan 2 contains data-price-usd='$8.00'");
        $this->assert(str_contains($htmlDefault, 'data-price-kes="KSh 2,500"'), "Plan 5 contains data-price-kes='KSh 2,500'");
        $this->assert(str_contains($htmlDefault, 'data-price-usd="$20.00"'), "Plan 5 contains data-price-usd='$20.00'");
        $this->assert(str_contains($htmlDefault, 'data-price-kes="KSh 10,000"'), "Plan 3 contains data-price-kes='KSh 10,000'");
        $this->assert(str_contains($htmlDefault, 'data-price-usd="$80.00"'), "Plan 3 contains data-price-usd='$80.00'");
        $this->assert(str_contains($htmlDefault, 'data-price-kes="KSh 20,000"'), "Plan 4 contains data-price-kes='KSh 20,000'");
        $this->assert(str_contains($htmlDefault, 'data-price-usd="$160.00"'), "Plan 4 contains data-price-usd='$160.00'");
        $this->assert(str_contains($htmlDefault, 'plan=standard-monthly&currency=USD') || str_contains($htmlDefault, 'plan=standard-monthly&amp;currency=USD'), "Plan 2 has data-cta-usd with currency=USD parameter");
        $this->assert(str_contains($htmlDefault, 'plan=pro-monthly&currency=USD') || str_contains($htmlDefault, 'plan=pro-monthly&amp;currency=USD'), "Plan 5 has data-cta-usd with currency=USD parameter");

        // 2b. Request with ?currency=USD
        $_SESSION = [];
        $requestUSD = new Request(['currency' => 'USD'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/pricing?currency=USD'], [], []);
        $respUSD = $homeController->pricing($requestUSD);
        $htmlUSD = $respUSD->getContent();

        $this->assert($_SESSION['currency'] === 'USD', "Session currency set to USD when ?currency=USD provided");
        $this->assert(str_contains($htmlUSD, 'id="currencyBtnUSD"') && str_contains($htmlUSD, 'aria-checked="true"'), "USD button is marked active and aria-checked='true' when currency is USD");
        $this->assert(str_contains($htmlUSD, '$8.00'), "Rendered pricing page includes initial USD price '$8.00' for Plan 2");
        $this->assert(str_contains($htmlUSD, '$20.00'), "Rendered pricing page includes initial USD price '$20.00' for Plan 5");

        // ----------------------------------------------------
        // 3. HomeController::setCurrency Endpoint
        // ----------------------------------------------------
        echo "\n--- 3. HomeController::setCurrency AJAX Endpoint ---\n";
        $setReqUSD = new Request([], ['currency' => 'USD', 'format' => 'json'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/currency'], ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], []);
        $setRespUSD = $homeController->setCurrency($setReqUSD);
        $setDataUSD = json_decode($setRespUSD->getContent(), true);

        $this->assert($setDataUSD['success'] === true, "POST /currency with 'USD' returns success: true");
        $this->assert($setDataUSD['currency'] === 'USD', "POST /currency returns currency: 'USD'");
        $this->assert($_SESSION['currency'] === 'USD', "Session currency updated to USD");

        $setReqInvalid = new Request([], ['currency' => 'BITCOIN', 'format' => 'json'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => '/currency'], ['HTTP_ACCEPT' => 'application/json', 'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'], []);
        $setRespInvalid = $homeController->setCurrency($setReqInvalid);
        $setDataInvalid = json_decode($setRespInvalid->getContent(), true);

        $this->assert($setDataInvalid['success'] === false, "POST /currency with unsupported currency returns success: false");
        $this->assert(str_contains($setDataInvalid['error'] ?? '', 'Unsupported currency'), "Appropriate error message for unsupported currency");

        // ----------------------------------------------------
        // 4. AuthController Registration Flow Persistence
        // ----------------------------------------------------
        echo "\n--- 4. AuthController Registration Currency Persistence ---\n";
        $authController = new AuthController();
        $_SESSION = [];
        $regRequest = new Request(['plan' => 'pro-monthly', 'currency' => 'USD'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/register?plan=pro-monthly&currency=USD'], [], []);
        $regResp = $authController->registerForm($regRequest);

        $this->assert($_SESSION['currency'] === 'USD', "AuthController captures ?currency=USD into session");
        $this->assert($_SESSION['selected_plan'] === 'pro-monthly', "AuthController captures ?plan=pro-monthly into session");

        // ----------------------------------------------------
        // 5. Tenant BillingController Gateway Auto-Detection
        // ----------------------------------------------------
        echo "\n--- 5. Tenant BillingController Gateway & Currency Integration ---\n";
        // Setup a test tenant organization
        $testOrgId = Ulid::generate();
        $this->pdo->exec("INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at) VALUES ('{$testOrgId}', 'Dual Curr Test Org', 'dual-curr-{$testOrgId}', 'KE', 'Africa/Nairobi', NOW(), NOW())");

        $_SESSION['organization_id'] = $testOrgId;
        $_SESSION['role'] = 'owner';

        $billingController = new BillingController();

        // 5a. When currency is USD
        $_SESSION['currency'] = 'USD';
        $billingReqUSD = new Request(['currency' => 'USD'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tenant/billing?currency=USD'], [], []);
        $billingReqUSD->setAttribute('tenant', ['id' => $testOrgId, 'name' => 'Dual Curr Test Org', 'slug' => "dual-curr-{$testOrgId}"]);
        $lvl = ob_get_level();
        ob_start();
        $billingRespUSD = $billingController->index($billingReqUSD);
        $billingHtmlUSD = $billingRespUSD->getContent();
        while (ob_get_level() > $lvl) {
            ob_end_clean();
        }

        $this->assert(str_contains($billingHtmlUSD, 'id="method-paypal" checked') || str_contains($billingHtmlUSD, 'value="paypal" checked'), "Billing page auto-selects PayPal as default method when currency is USD");

        // 5b. When currency is KES
        $_SESSION['currency'] = 'KES';
        $billingReqKES = new Request(['currency' => 'KES'], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/tenant/billing?currency=KES'], [], []);
        $billingReqKES->setAttribute('tenant', ['id' => $testOrgId, 'name' => 'Dual Curr Test Org', 'slug' => "dual-curr-{$testOrgId}"]);
        $lvl = ob_get_level();
        ob_start();
        $billingRespKES = $billingController->index($billingReqKES);
        $billingHtmlKES = $billingRespKES->getContent();
        while (ob_get_level() > $lvl) {
            ob_end_clean();
        }

        $this->assert(str_contains($billingHtmlKES, 'id="method-mpesa" checked') || str_contains($billingHtmlKES, 'value="mpesa" checked'), "Billing page auto-selects M-Pesa as default method when currency is KES");

        // ----------------------------------------------------
        // 6. PaymentService Security, Validation & Tampering
        // ----------------------------------------------------
        echo "\n--- 6. PaymentService Gateway-Currency Enforcement & Security ---\n";

        // 6a. M-Pesa with USD currency must fail
        $intentMpesaUSD = $this->paymentService->createPaymentIntent(
            $testOrgId,
            null,
            5, // Pro Monthly
            'mpesa',
            ['phone' => '0712345678', 'currency' => 'USD']
        );
        $this->assert($intentMpesaUSD['success'] === false, "PaymentService rejects M-Pesa with USD currency");
        $this->assert(str_contains($intentMpesaUSD['error'] ?? '', 'M-Pesa payment gateway only supports transactions in KES'), "Clear error that M-Pesa only supports KES");

        // 6b. PayPal with unsupported currency (e.g. 'XYZ') must fail
        $intentPaypalInvalid = $this->paymentService->createPaymentIntent(
            $testOrgId,
            null,
            5,
            'paypal',
            ['currency' => 'XYZ']
        );
        $this->assert($intentPaypalInvalid['success'] === false, "PaymentService rejects PayPal with unsupported currency 'XYZ'");
        $this->assert(str_contains($intentPaypalInvalid['error'] ?? '', 'Unsupported currency'), "Clear error for unsupported PayPal currency");

        // 6c. PayPal with price tampering must fail
        $intentTampered = $this->paymentService->createPaymentIntent(
            $testOrgId,
            null,
            5, // Plan 5 is $20.00
            'paypal',
            ['custom_amount' => 1.00, 'currency' => 'USD'] // Attacker tries to submit $1.00
        );
        $this->assert($intentTampered['success'] === false, "PaymentService detects and rejects tampered international price ($1.00 instead of $20.00)");
        $this->assert(str_contains($intentTampered['error'] ?? '', 'does not match official plan pricing'), "Tampering attempt returned official plan pricing mismatch error");

        // 6d. PayPal with authoritative price ($20.00 for Plan 5) creates valid intent
        $intentValidUSD = $this->paymentService->createPaymentIntent(
            $testOrgId,
            null,
            5,
            'paypal',
            ['currency' => 'USD']
        );
        $this->assert($intentValidUSD['success'] === true, "Valid USD payment intent for Plan 5 created successfully");
        $this->assert((float)$intentValidUSD['amount'] === 20.0, "Intent amount is 20.00");
        $this->assert($intentValidUSD['currency'] === 'USD', "Intent currency is USD");

        // Check intent record in database
        $intentId = $intentValidUSD['intent_id'];
        $stmtIntent = $this->pdo->prepare("SELECT * FROM payment_intents WHERE id = :id");
        $stmtIntent->execute(['id' => $intentId]);
        $dbIntent = $stmtIntent->fetch(\PDO::FETCH_ASSOC);

        $this->assert(!empty($dbIntent), "Payment intent persisted in database");
        $this->assert($dbIntent['currency'] === 'USD', "Persisted intent currency is 'USD'");
        $this->assert((float)$dbIntent['amount'] === 20.0, "Persisted intent amount is 20.00");
        $this->assert($dbIntent['base_currency'] === 'KES', "Persisted intent base_currency is 'KES'");
        $this->assert((float)$dbIntent['base_amount'] === 2500.0, "Persisted intent base_amount matches Plan 5 KES price (2,500.00)");

        // Clean up test data
        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id = '{$testOrgId}'");
        $this->pdo->exec("DELETE FROM organizations WHERE id = '{$testOrgId}'");

        echo "\n========================================================\n";
        echo " DUAL CURRENCY SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "========================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }
}

$test = new DualCurrencyPricingTest();
$test->run();
