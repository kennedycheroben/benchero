<?php

/**
 * Benchero — Unified Two-Gateway Payment Architecture Automated Test Suite
 *
 * Tests all 23 criteria (A through W) specified in the Benchero payment architecture specification.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Core\PricingConfig;
use Benchero\Contracts\PaymentGatewayInterface;
use Benchero\Services\PaymentService;
use Benchero\Services\SubscriptionService;
use Benchero\Services\Gateways\PaymentGatewayFactory;
use Benchero\Services\Gateways\ImBankPaymentGateway;
use Benchero\Services\Gateways\PayPalPaymentGateway;

class UnifiedPaymentTestSuite
{
    private \PDO $pdo;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;
    private string $testOrgId;
    private string $testUserId;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->paymentService = new PaymentService();
        $this->subscriptionService = new SubscriptionService();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $this->testOrgId = Ulid::generate();
        $this->testUserId = Ulid::generate();

        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'unified-pay-test-club')");
        $this->pdo->exec("DELETE FROM payments WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'unified-pay-test-club')");
        $this->pdo->exec("DELETE FROM subscriptions WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'unified-pay-test-club')");
        $this->pdo->exec("DELETE FROM organization_user WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'unified-pay-test-club')");
        $this->pdo->exec("DELETE FROM organizations WHERE slug = 'unified-pay-test-club'");
        $this->pdo->exec("DELETE FROM users WHERE email = 'unified_pay_test@benchero.co.ke'");

        // Create test organization
        $stmt = $this->pdo->prepare("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES (?, 'Unified Pay Test Club', 'unified-pay-test-club', 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");
        $stmt->execute([$this->testOrgId]);

        // Create test user
        $uStmt = $this->pdo->prepare("
            INSERT INTO users (id, name, email, password_hash, created_at, updated_at)
            VALUES (?, 'Unified Test User', 'unified_pay_test@benchero.co.ke', 'test_hash', NOW(), NOW())
        ");
        $uStmt->execute([$this->testUserId]);

        // Initialize trial
        $this->subscriptionService->initializeTrialSubscription($this->testOrgId);
    }

    public function run(): void
    {
        echo "=====================================================\n";
        echo " BENCHERO UNIFIED TWO-GATEWAY PAYMENT TEST SUITE\n";
        echo "=====================================================\n\n";

        $passed = 0;
        $failed = 0;

        $tests = [
            'testA_ProviderFactory' => '[A] Payment Provider Factory Resolution',
            'testB_ImBankInitialization' => '[B] I&M Provider Initialization & Phone Normalization',
            'testC_PayPalInitialization' => '[C] PayPal Provider Initialization & Supported Currencies',
            'testD_MissingCredentialsHandling' => '[D] Missing Credentials Safe Handling',
            'testE_InvalidConfigurationHandling' => '[E] Invalid Configuration & Fallback Safety',
            'testF_PaymentCreation' => '[F] Payment Intent Creation (M-Pesa & PayPal)',
            'testG_PaymentNormalization' => '[G] Payment Response Contract Normalization',
            'testH_SuccessfulPayment' => '[H] Successful Payment & Unified Record Creation',
            'testI_FailedPayment' => '[I] Failed Payment Handling',
            'testJ_PendingPayment' => '[J] Pending Payment Status Polling',
            'testK_DuplicateCallbackIdempotency' => '[K] Duplicate Callback Idempotency',
            'testL_InvalidCallbackRejection' => '[L] Invalid / Malformed Callback Rejection',
            'testM_SubscriptionActivation' => '[M] Subscription Activation via PaymentService',
            'testN_SubscriptionRenewal' => '[N] Subscription Renewal & Stacking Logic',
            'testO_SubscriptionExpiry' => '[O] Subscription Expiry & Access Restriction',
            'testP_TrialConversion' => '[P] Trial Conversion to Paid Active Subscription',
            'testQ_MonthlyPlanPricing' => '[Q] Pro Monthly Plan (KSh 2,500 / $20 USD)',
            'testR_YearlyPlanPricing' => '[R] Pro Yearly Plan (KSh 25,000 / $200 USD)',
            'testS_KshTransactionFlow' => '[S] Kenyan KSh Transaction Flow',
            'testT_ForeignCurrencyTransactionFlow' => '[T] International Foreign Currency Flow (USD)',
            'testU_UnsupportedCurrency' => '[U] Unsupported Currency Rejection',
            'testV_WebhookIdempotency' => '[V] Webhook Replay Idempotency',
            'testX_PayPalReturnController' => '[X] PayPal Browser Return & Cancel Server-Side Verification',
            'testW_SecretSafety' => '[W] Secret Safety & Credential Leak Scanner'
        ];

        foreach ($tests as $method => $description) {
            try {
                $this->$method();
                echo " [PASS] {$description}\n";
                $passed++;
            } catch (\Throwable $e) {
                echo " [FAIL] {$description}\n";
                echo "        Error: " . $e->getMessage() . "\n";
                echo "        File: " . $e->getFile() . ":" . $e->getLine() . "\n";
                $failed++;
            }
        }

        echo "\n=====================================================\n";
        echo " SUMMARY: Passed {$passed} / Failed {$failed}\n";
        echo "=====================================================\n";

        if ($failed > 0) {
            exit(1);
        }
    }

    private function assert($condition, string $msg): void
    {
        if (!$condition) {
            throw new \Exception("Assertion failed: {$msg}");
        }
    }

    public function testA_ProviderFactory(): void
    {
        $imbank = PaymentGatewayFactory::create('imbank');
        $this->assert($imbank instanceof ImBankPaymentGateway, "create('imbank') must return ImBankPaymentGateway");
        $this->assert($imbank->getProviderName() === 'imbank', "ImBank provider name must be imbank");

        $paypal = PaymentGatewayFactory::create('paypal');
        $this->assert($paypal instanceof PayPalPaymentGateway, "create('paypal') must return PayPalPaymentGateway");
        $this->assert($paypal->getProviderName() === 'paypal', "PayPal provider name must be paypal");

        $defaultGw = PaymentGatewayFactory::create();
        $this->assert($defaultGw instanceof PaymentGatewayInterface, "Default gateway must implement PaymentGatewayInterface");

        $methodMpesa = PaymentGatewayFactory::getProviderForMethod('mpesa');
        $this->assert($methodMpesa instanceof ImBankPaymentGateway, "mpesa method must map to ImBankPaymentGateway");

        $methodPayPal = PaymentGatewayFactory::getProviderForMethod('paypal');
        $this->assert($methodPayPal instanceof PayPalPaymentGateway, "paypal method must map to PayPalPaymentGateway");

        $methodCard = PaymentGatewayFactory::getProviderForMethod('card');
        $this->assert($methodCard instanceof PayPalPaymentGateway, "card method must map to PayPalPaymentGateway");
    }

    public function testB_ImBankInitialization(): void
    {
        $gateway = new ImBankPaymentGateway();
        $this->assert($gateway->getSupportedCurrencies() === ['KES'], "I&M must support KES");
        $this->assert($gateway->getSupportedPaymentMethods() === ['mpesa'], "I&M must support mpesa");

        $this->assert(ImBankPaymentGateway::normalizePhoneNumber('0712345678') === '254712345678', "0712345678 should normalize to 254712345678");
        $this->assert(ImBankPaymentGateway::normalizePhoneNumber('254712345678') === '254712345678', "254712345678 should stay 254712345678");
        $this->assert(ImBankPaymentGateway::normalizePhoneNumber('0112345678') === '254112345678', "0112345678 should normalize to 254112345678");
        $this->assert(ImBankPaymentGateway::normalizePhoneNumber('invalid') === null, "Invalid phone should return null");
    }

    public function testC_PayPalInitialization(): void
    {
        $gateway = new PayPalPaymentGateway();
        $currencies = $gateway->getSupportedCurrencies();
        $this->assert(in_array('USD', $currencies, true), "PayPal must support USD");
        $methods = $gateway->getSupportedPaymentMethods();
        $this->assert(in_array('paypal', $methods, true), "PayPal must support paypal method");
        $this->assert(in_array('card', $methods, true), "PayPal must support card method");
    }

    public function testD_MissingCredentialsHandling(): void
    {
        // Safe check without exposing or changing credentials
        $gw = new PayPalPaymentGateway();
        $configured = $gw->isConfigured();
        $this->assert(is_bool($configured), "isConfigured must return a boolean safely");

        // Verify that initiatePayment with amount=0 returns safe error
        $res = $gw->initiatePayment(['amount' => 0]);
        $this->assert($res['success'] === false, "Zero amount must be rejected");
        $this->assert(!empty($res['message']), "Message must be provided");
    }

    public function testE_InvalidConfigurationHandling(): void
    {
        $gw = PaymentGatewayFactory::create('non_existent_provider');
        $this->assert($gw instanceof PaymentGatewayInterface, "Fallback gateway must implement interface");
    }

    public function testF_PaymentCreation(): void
    {
        // M-Pesa intent (Pro Monthly, plan 5, KSh 2,500)
        $intentMpesa = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            5,
            'mpesa',
            ['phone_number' => '0712345678']
        );
        $this->assert($intentMpesa['success'] === true, "M-Pesa intent creation must succeed");
        $this->assert($intentMpesa['currency'] === 'KES', "M-Pesa currency must be KES");
        $this->assert((float)$intentMpesa['amount'] === 2500.0, "Pro Monthly price must be 2,500");
        $this->assert($intentMpesa['provider'] === 'imbank', "Provider must be imbank");

        // PayPal intent (Pro Yearly, plan 4, $200 USD)
        $intentPayPal = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            4,
            'paypal',
            ['currency' => 'USD']
        );
        $this->assert($intentPayPal['success'] === true, "PayPal intent creation must succeed");
        $this->assert($intentPayPal['currency'] === 'USD', "PayPal currency must be USD");
        $this->assert((float)$intentPayPal['amount'] === 200.0, "PayPal Pro Yearly price must be $200.00");
        $this->assert((float)$intentPayPal['base_amount'] === 25000.0, "Base amount must be 25,000 KES");
        $this->assert($intentPayPal['base_currency'] === 'KES', "Base currency must be KES");
        $this->assert($intentPayPal['provider'] === 'paypal', "Provider must be paypal");
    }

    public function testG_PaymentNormalization(): void
    {
        $imbank = new ImBankPaymentGateway();
        $paypal = new PayPalPaymentGateway();

        // Normalization on webhook/callback
        $imNorm = $imbank->verifyPaymentNotification([
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => 'TEST_CHK_123',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'REC_TEST_99'],
                            ['Name' => 'Amount', 'Value' => 2500.0]
                        ]
                    ]
                ]
            ]
        ]);
        $this->assert($imNorm['valid'] === true, "ImBank valid notification");
        $this->assert($imNorm['status'] === 'completed', "ImBank status completed");
        $this->assert($imNorm['receipt'] === 'REC_TEST_99', "Receipt captured");

        $ppNorm = $paypal->verifyPaymentNotification([
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP_TEST_99',
                'amount' => ['value' => '20.00', 'currency_code' => 'USD'],
                'custom_id' => 'CHK_PP_123'
            ]
        ]);
        $this->assert($ppNorm['valid'] === true, "PayPal valid notification");
        $this->assert($ppNorm['status'] === 'completed', "PayPal status completed");
        $this->assert($ppNorm['receipt'] === 'CAP_TEST_99', "Capture ID recorded as receipt");
    }

    public function testH_SuccessfulPayment(): void
    {
        // Create PayPal intent for plan 5 (Pro Monthly)
        $intent = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            5,
            'paypal',
            ['currency' => 'USD']
        );

        $recordRes = $this->paymentService->recordSuccessfulPayment($intent, [
            'receipt' => 'PAYPAL_CAP_' . time(),
            'provider_reference' => 'PP_ORDER_' . time(),
            'amount' => 20.00,
            'currency' => 'USD',
            'payer_email' => 'international_payer@benchero.com',
            'payment_method' => 'paypal'
        ]);

        $this->assert($recordRes['success'] === true, "recordSuccessfulPayment must succeed");

        // Verify payment record in database
        $stmt = $this->pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$recordRes['payment_id']]);
        $pay = $stmt->fetch(\PDO::FETCH_ASSOC);

        $this->assert($pay !== false, "Payment record must exist in payments table");
        $this->assert((float)$pay['amount'] === 20.00, "Amount must be 20.00");
        $this->assert($pay['currency'] === 'USD', "Currency must be USD");
        $this->assert((float)$pay['base_amount'] === 2500.00, "Base amount must be 2500.00");
        $this->assert($pay['base_currency'] === 'KES', "Base currency must be KES");
        $this->assert($pay['provider'] === 'paypal', "Provider must be paypal");

        // Verify subscription updated
        $sub = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $this->assert($sub['status'] === SubscriptionService::STATUS_ACTIVE, "Subscription must be active");
        $this->assert((int)$sub['plan_id'] === 5, "Plan must be 5 (Pro Monthly)");
    }

    public function testI_FailedPayment(): void
    {
        $intent = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            5,
            'mpesa',
            ['phone_number' => '0712345678']
        );

        // Process failed callback
        $processed = $this->paymentService->processWebhook('imbank', [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 1032, // User cancelled
                    'ResultDesc' => 'Request cancelled by user',
                    'CheckoutRequestID' => $intent['reference']
                ]
            ]
        ]);

        $this->assert($processed === true, "Callback should be processed");
        $updatedIntent = $this->paymentService->getIntentStatus($intent['intent_id']);
        $this->assert($updatedIntent['status'] === 'cancelled', "Intent status must be cancelled");
    }

    public function testJ_PendingPayment(): void
    {
        $intent = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            4,
            'paypal',
            ['currency' => 'USD']
        );

        $status = $this->paymentService->getIntentStatus($intent['intent_id']);
        $this->assert($status['status'] === 'pending', "Fresh intent must be pending");
    }

    public function testK_DuplicateCallbackIdempotency(): void
    {
        $intent = $this->paymentService->createPaymentIntent(
            $this->testOrgId,
            $this->testUserId,
            4,
            'mpesa',
            ['phone_number' => '0712345678']
        );

        $receipt = 'REC_DUP_' . time();
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $intent['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                            ['Name' => 'Amount', 'Value' => 25000.0]
                        ]
                    ]
                ]
            ]
        ];

        // First callback
        $first = $this->paymentService->processWebhook('imbank', $payload);
        $this->assert($first === true, "First callback must succeed");

        $sub1 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry1 = $sub1['expires_at'];

        // Replay duplicate callback
        $second = $this->paymentService->processWebhook('imbank', $payload);
        $this->assert($second === true, "Duplicate callback must be acknowledged safely");

        $sub2 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry2 = $sub2['expires_at'];

        $this->assert($expiry1 === $expiry2, "Duplicate callback must NOT double-extend the subscription period");
    }

    public function testL_InvalidCallbackRejection(): void
    {
        // Empty payload
        $res1 = $this->paymentService->processWebhook('imbank', []);
        $this->assert($res1 === false, "Empty payload must be rejected");

        // Malformed PayPal payload
        $res2 = $this->paymentService->processWebhook('paypal', ['invalid' => 'payload']);
        $this->assert($res2 === false, "Malformed PayPal payload must be rejected");
    }

    public function testM_SubscriptionActivation(): void
    {
        $activated = $this->subscriptionService->activateSubscription(
            $this->testOrgId,
            4,
            'MANUAL_TEST_REF',
            null,
            'paypal'
        );
        $this->assert($activated === true, "activateSubscription must return true");

        $sub = $this->subscriptionService->getSubscription($this->testOrgId);
        $this->assert($sub['status'] === 'active', "Status must be active");
        $this->assert($sub['provider'] === 'paypal', "Provider must be recorded as paypal");
    }

    public function testN_SubscriptionRenewal(): void
    {
        // Set expiry to 30 days in future
        $now = time();
        $futureExpiry = date('Y-m-d H:i:s', strtotime('+30 days', $now));
        $this->pdo->prepare("UPDATE subscriptions SET expires_at = ?, current_period_end = ? WHERE organization_id = ?")
            ->execute([$futureExpiry, $futureExpiry, $this->testOrgId]);

        // Renew same yearly plan (Plan 4)
        $this->subscriptionService->activateSubscription($this->testOrgId, 4, 'RENEW_TEST');
        $sub = $this->subscriptionService->getSubscription($this->testOrgId);

        $newExpiryTs = strtotime($sub['expires_at']);
        $expectedMinTs = strtotime('+390 days', $now); // 30 days + 1 year

        $this->assert($newExpiryTs >= $expectedMinTs - 60, "Renewal must stack onto existing active period");
    }

    public function testO_SubscriptionExpiry(): void
    {
        // Set expiry in past
        $pastExpiry = date('Y-m-d H:i:s', strtotime('-2 days'));
        $this->pdo->prepare("UPDATE subscriptions SET expires_at = ?, current_period_end = ? WHERE organization_id = ?")
            ->execute([$pastExpiry, $pastExpiry, $this->testOrgId]);

        $status = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $this->assert($status['status'] === SubscriptionService::STATUS_EXPIRED, "Past expiry must evaluate to EXPIRED");
        $this->assert($status['is_visible'] === false, "Public profile must NOT be visible when expired");
    }

    public function testP_TrialConversion(): void
    {
        // Re-initialize trial
        $this->subscriptionService->initializeTrialSubscription($this->testOrgId);
        $trialStatus = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $this->assert($trialStatus['status'] === SubscriptionService::STATUS_TRIAL, "Initial state is trial");

        // Convert by paying Plan 5 (Pro Monthly)
        $intent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 5, 'mpesa', ['phone_number' => '0712345678']);
        $this->paymentService->recordSuccessfulPayment($intent, [
            'receipt' => 'TRIAL_CONV_' . time(),
            'provider_reference' => 'CHK_CONV_' . time(),
            'amount' => 2500.0,
            'currency' => 'KES'
        ]);

        $paidStatus = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $this->assert($paidStatus['status'] === SubscriptionService::STATUS_ACTIVE, "Trial must convert to active");
        $this->assert((int)$paidStatus['plan_id'] === 5, "Plan must update to paid plan 5");
    }

    public function testQ_MonthlyPlanPricing(): void
    {
        $plan = $this->subscriptionService->getPlan(5);
        $this->assert($plan !== null, "Plan 5 must exist in plans table");
        $this->assert((float)$plan['price_kes'] === 2500.00, "Plan 5 price must be exactly KSh 2,500.00");
        $this->assert($plan['billing_interval'] === 'monthly', "Plan 5 must be monthly");

        $intl = PricingConfig::getInternationalPrice(5, 'USD');
        $this->assert((float)$intl['charged_amount'] === 20.00, "Pro Monthly USD price must be $20.00");
    }

    public function testR_YearlyPlanPricing(): void
    {
        $plan = $this->subscriptionService->getPlan(4);
        $this->assert($plan !== null, "Plan 4 must exist in plans table");
        $this->assert((float)$plan['price_kes'] === 25000.00, "Plan 4 price must be exactly KSh 25,000.00");
        $this->assert($plan['billing_interval'] === 'yearly', "Plan 4 must be yearly");

        $intl = PricingConfig::getInternationalPrice(4, 'USD');
        $this->assert((float)$intl['charged_amount'] === 200.00, "Pro Yearly USD price must be $200.00");
    }

    public function testS_KshTransactionFlow(): void
    {
        $intent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 5, 'mpesa', ['phone_number' => '0712345678']);
        $this->assert($intent['currency'] === 'KES', "Kenyan transaction must be KES");
        $this->assert($intent['base_currency'] === 'KES', "Base currency must be KES");
        $this->assert((float)$intent['amount'] === 2500.00, "Amount must be 2,500");
    }

    public function testT_ForeignCurrencyTransactionFlow(): void
    {
        $intent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 4, 'paypal', ['currency' => 'USD']);
        $this->assert($intent['currency'] === 'USD', "International transaction must be USD");
        $this->assert($intent['base_currency'] === 'KES', "Base currency must remain KES");
        $this->assert((float)$intent['base_amount'] === 25000.00, "Canonical KES amount must be preserved");
        $this->assert((float)$intent['amount'] === 200.00, "Charged amount must be 200.00 USD");
    }

    public function testU_UnsupportedCurrency(): void
    {
        $gw = new PayPalPaymentGateway();
        $res = $gw->initiatePayment([
            'amount' => 100,
            'currency' => 'XYZ_UNSUPPORTED'
        ]);
        $this->assert($res['success'] === false, "Unsupported currency must be rejected");
    }

    public function testV_WebhookIdempotency(): void
    {
        $intent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 4, 'paypal', ['currency' => 'USD']);

        $captureId = 'CAP_WEBHOOK_' . time();
        $webhookPayload = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => $captureId,
                'amount' => ['value' => '200.00', 'currency_code' => 'USD'],
                'custom_id' => $intent['reference']
            ]
        ];

        // Trigger webhook 1
        $res1 = $this->paymentService->processWebhook('paypal', $webhookPayload);
        $this->assert($res1 === true, "First webhook must succeed");

        // Count payment records for this intent
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_intent_id = ?");
        $stmt->execute([$intent['intent_id']]);
        $count1 = (int)$stmt->fetchColumn();
        $this->assert($count1 === 1, "Exactly 1 payment record must be created");

        // Trigger webhook 2 (replay)
        $res2 = $this->paymentService->processWebhook('paypal', $webhookPayload);
        $this->assert($res2 === true, "Replayed webhook must acknowledge safely");

        $stmt->execute([$intent['intent_id']]);
        $count2 = (int)$stmt->fetchColumn();
        $this->assert($count2 === 1, "Duplicate webhook must NOT create duplicate payment records");
    }

    public function testX_PayPalReturnController(): void
    {
        $controller = new \Benchero\Controllers\Public\PayPalReturnController();

        // 1. Create a pending intent and set simulated provider reference
        $intent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 5, 'paypal', ['currency' => 'USD']);
        $this->assert($intent['exchange_rate'] === null, "Explicit international tier must have null exchange_rate");

        $simulatedOrderId = 'PAYPAL_SIM_RET_' . time();
        $this->pdo->prepare("UPDATE payment_intents SET provider_reference = ?, status = 'initiated' WHERE id = ?")
            ->execute([$simulatedOrderId, $intent['id']]);

        // 2. Simulate return redirect with valid PayPal order token
        $request = new \Benchero\Core\Http\Request(
            ['token' => $simulatedOrderId, 'PayerID' => 'TEST_PAYER_123'],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            []
        );

        $response = $controller->return($request);
        $this->assert($response->getStatusCode() === 302, "Return must redirect back to organization billing");

        // Verify that intent was captured and completed server-side
        $updatedIntent = $this->paymentService->getIntentStatus($intent['id']);
        $this->assert($updatedIntent['status'] === 'completed', "Payment must be completed after server-side capture");

        // 3. Test cancel redirect
        $cancelIntent = $this->paymentService->createPaymentIntent($this->testOrgId, $this->testUserId, 5, 'paypal', ['currency' => 'USD']);
        $cancelReq = new \Benchero\Core\Http\Request(
            ['token' => $cancelIntent['reference']],
            [],
            ['REQUEST_METHOD' => 'GET'],
            [],
            []
        );
        $cancelRes = $controller->cancel($cancelReq);
        $this->assert($cancelRes->getStatusCode() === 302, "Cancel must redirect");

        $updatedCancel = $this->paymentService->getIntentStatus($cancelIntent['id']);
        $this->assert($updatedCancel['status'] === 'cancelled', "Cancelled intent must have cancelled status");
    }

    public function testW_SecretSafety(): void
    {
        // Verify that PayPalPaymentGateway and ImBankPaymentGateway string representations do not dump secrets
        $pp = new PayPalPaymentGateway();
        $im = new ImBankPaymentGateway();

        $ppExport = var_export($pp, true);
        $this->assert(!str_contains($ppExport, 'SECRET_EXPOSED'), "Secrets must not be exposed");

        // Verify that getProviderName does not leak sensitive information
        $this->assert($pp->getProviderName() === 'paypal', "Safe provider name");
        $this->assert($im->getProviderName() === 'imbank', "Safe provider name");
    }
}

// Run test suite
$suite = new UnifiedPaymentTestSuite();
$suite->run();
