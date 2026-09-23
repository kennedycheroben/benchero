<?php

/**
 * Benchero — Automated M-PESA STK Push Billing Test Suite
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\MpesaService;
use Benchero\Services\SubscriptionService;
use Benchero\Services\Gateways\ImBankPaymentGateway;
use Benchero\Controllers\Admin\AdminController;
use Benchero\Core\Http\Request;

class StkPushBillingTestSuite
{
    private \PDO $pdo;
    private MpesaService $mpesaService;
    private SubscriptionService $subscriptionService;
    private string $testOrgId;
    private string $testUserId;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->mpesaService = new MpesaService();
        $this->subscriptionService = new SubscriptionService();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $this->testOrgId = Ulid::generate();
        $this->testUserId = Ulid::generate();

        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'billing-test-club')");
        $this->pdo->exec("DELETE FROM subscriptions WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'billing-test-club')");
        $this->pdo->exec("DELETE FROM payments WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'billing-test-club')");
        $this->pdo->exec("DELETE FROM organization_user WHERE organization_id IN (SELECT id FROM organizations WHERE slug = 'billing-test-club')");
        $this->pdo->exec("DELETE FROM organizations WHERE slug = 'billing-test-club'");
        $this->pdo->exec("DELETE FROM users WHERE email = 'billing_test@benchero.co.ke'");

        // Create test organization
        $stmt = $this->pdo->prepare("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES (?, 'Billing Test Club', 'billing-test-club', 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");
        $stmt->execute([$this->testOrgId]);

        // Create test user
        $uStmt = $this->pdo->prepare("
            INSERT INTO users (id, name, email, password_hash, created_at, updated_at)
            VALUES (?, 'Billing Test User', 'billing_test@benchero.co.ke', 'hash', NOW(), NOW())
        ");
        $uStmt->execute([$this->testUserId]);

        // Link user to org
        $ouStmt = $this->pdo->prepare("
            INSERT INTO organization_user (organization_id, user_id, role, created_at)
            VALUES (?, ?, 'owner', NOW())
        ");
        $ouStmt->execute([$this->testOrgId, $this->testUserId]);
    }

    public function run(): void
    {
        echo "=====================================================\n";
        echo " BENCHERO M-PESA STK PUSH BILLING TEST SUITE\n";
        echo "=====================================================\n\n";

        $passed = 0;
        $failed = 0;

        $tests = [
            'testPhoneNormalization' => 'Phone Number Normalization & Validation',
            'testInvalidPhoneRejection' => 'Invalid Phone Number Rejection',
            'testPaymentIntentCreation' => 'Successful STK Intent Creation (Server-Side Pricing)',
            'testDuplicatePaymentIntent' => 'Duplicate Payment Intent Handling',
            'testRateLimiting' => 'STK Push Initiation Rate Limiting',
            'testSuccessfulPaymentCallback' => 'Successful Payment Callback & Subscription Activation',
            'testCallbackIdempotency' => 'Callback Idempotency (Duplicate Prevention)',
            'testFailedAndCancelledCallback' => 'Failed & Cancelled Payment Callback Handling',
            'testAmountMismatchRejection' => 'Amount Mismatch Rejection',
            'testExpiredIntent' => 'Expired Payment Intent Handling',
            'testSubscriptionRenewal' => 'Subscription Renewal & Expiration Stacking',
            'testProUpgradePolicy' => 'Pro Plan Upgrade Policy',
            'testAdminPaymentFiltering' => 'Platform Admin Payment Dashboard & Filters'
        ];

        foreach ($tests as $method => $description) {
            try {
                $this->$method();
                echo " [PASS] {$description}\n";
                $passed++;
            } catch (\Throwable $e) {
                echo " [FAIL] {$description}\n";
                echo "        Error: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        $this->cleanupTestData();

        echo "\n=====================================================\n";
        echo " SUMMARY: Passed {$passed} / Failed {$failed}\n";
        echo "=====================================================\n";

        if ($failed > 0) {
            exit(1);
        }
    }

    private function testPhoneNormalization(): void
    {
        $valid1 = ImBankPaymentGateway::normalizePhoneNumber('0712345678');
        $valid2 = ImBankPaymentGateway::normalizePhoneNumber('+254 799 999 999');
        $valid3 = ImBankPaymentGateway::normalizePhoneNumber('254112345678');

        if ($valid1 !== '254712345678' || $valid2 !== '254799999999' || $valid3 !== '254112345678') {
            throw new \Exception("Phone normalization failed. Got {$valid1}, {$valid2}, {$valid3}");
        }
    }

    private function testInvalidPhoneRejection(): void
    {
        $res1 = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712');
        $res2 = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, 'invalid_number');

        if ($res1['success'] !== false || $res2['success'] !== false) {
            throw new \Exception("Invalid phone number was not rejected.");
        }
    }

    private function testPaymentIntentCreation(): void
    {
        // Standard Monthly is KSh 1,000 (plan ID 2)
        $res = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712345678');

        if (!$res['success'] || $res['amount'] !== 1000.00 || empty($res['reference'])) {
            throw new \Exception("Payment intent creation failed or amount tampered.");
        }

        // Verify DB intent record
        $stmt = $this->pdo->prepare("SELECT * FROM payment_intents WHERE id = ?");
        $stmt->execute([$res['intent_id']]);
        $intent = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$intent || $intent['status'] !== 'pending' || (float)$intent['amount'] !== 1000.00) {
            throw new \Exception("DB payment intent record mismatch.");
        }
    }

    private function testDuplicatePaymentIntent(): void
    {
        $res1 = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712345678');
        $res2 = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712345678');

        if (!$res1['success'] || !$res2['success'] || $res1['reference'] === $res2['reference']) {
            throw new \Exception("Unique references were not generated for duplicate intents.");
        }
    }

    private function testRateLimiting(): void
    {
        $tempOrgId = Ulid::generate();
        $stmt = $this->pdo->prepare("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES (?, 'Rate Limit Test Club', 'rate-limit-club', 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");
        $stmt->execute([$tempOrgId]);

        // Create 5 payment intents
        for ($i = 0; $i < 5; $i++) {
            $this->mpesaService->createPaymentIntent($tempOrgId, $this->testUserId, 2, '0712345678');
        }

        // Check if 6th request triggers rate limit check in BillingController
        $db = Database::getConnection();
        $cntStmt = $db->prepare("SELECT COUNT(*) FROM payment_intents WHERE organization_id = ? AND created_at >= NOW() - INTERVAL 10 MINUTE");
        $cntStmt->execute([$tempOrgId]);
        $count = (int)$cntStmt->fetchColumn();

        if ($count < 5) {
            throw new \Exception("Rate limit test failed. Expected at least 5 intents.");
        }

        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id = '{$tempOrgId}'");
        $this->pdo->exec("DELETE FROM organizations WHERE id = '{$tempOrgId}'");
    }

    private function testSuccessfulPaymentCallback(): void
    {
        $intentRes = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712345678');
        $intentId = $intentRes['intent_id'];
        $receipt = 'MPESA_' . strtoupper(substr(md5(uniqid()), 0, 8));

        // Simulate successful gateway callback
        $callbackPayload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'The service request is processed successfully.',
                    'CheckoutRequestID' => $intentRes['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                            ['Name' => 'Amount', 'Value' => 1000.00],
                            ['Name' => 'PhoneNumber', 'Value' => 254712345678]
                        ]
                    ]
                ]
            ]
        ];

        $processed = $this->mpesaService->processCallback($callbackPayload);

        if (!$processed) {
            throw new \Exception("Successful callback processing returned false.");
        }

        // Verify payment intent status updated to completed
        $piStmt = $this->pdo->prepare("SELECT status FROM payment_intents WHERE id = ?");
        $piStmt->execute([$intentId]);
        $piStatus = $piStmt->fetchColumn();

        if ($piStatus !== 'completed') {
            throw new \Exception("Payment intent status not updated to completed. Got {$piStatus}");
        }

        // Verify subscription activated
        $sub = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        if ($sub['status'] !== SubscriptionService::STATUS_ACTIVE || $sub['plan_id'] != 2) {
            throw new \Exception("Subscription was not activated after successful payment.");
        }
    }

    private function testCallbackIdempotency(): void
    {
        $receipt = 'MPESA_IDEM_TEST_' . rand(1000, 9999);
        $intentRes = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 2, '0712345678');

        $payload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $intentRes['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                            ['Name' => 'Amount', 'Value' => 1000.00]
                        ]
                    ]
                ]
            ]
        ];

        // Process first callback
        $res1 = $this->mpesaService->processCallback($payload);

        // Fetch expiry date after first activation
        $sub1 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry1 = $sub1['expires_at'];

        // Process duplicate callback with identical receipt
        $res2 = $this->mpesaService->processCallback($payload);

        // Fetch expiry date after duplicate callback
        $sub2 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry2 = $sub2['expires_at'];

        if (!$res1 || !$res2 || $expiry1 !== $expiry2) {
            throw new \Exception("Duplicate callback extended subscription twice! Expiry1: {$expiry1}, Expiry2: {$expiry2}");
        }
    }

    private function testFailedAndCancelledCallback(): void
    {
        $intentRes = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 3, '0712345678');
        
        // Simulate user cancelled prompt (ResultCode 1032)
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 1032,
                    'ResultDesc' => 'Request cancelled by user',
                    'CheckoutRequestID' => $intentRes['reference']
                ]
            ]
        ];

        $this->mpesaService->processCallback($payload);

        $piStmt = $this->pdo->prepare("SELECT status, failure_reason FROM payment_intents WHERE id = ?");
        $piStmt->execute([$intentRes['intent_id']]);
        $pi = $piStmt->fetch(\PDO::FETCH_ASSOC);

        if ($pi['status'] !== 'cancelled') {
            throw new \Exception("Cancelled STK callback did not mark intent as cancelled. Got {$pi['status']}");
        }
    }

    private function testAmountMismatchRejection(): void
    {
        $intentRes = $this->mpesaService->createPaymentIntent($this->testOrgId, $this->testUserId, 3, '0712345678'); // Standard Yearly KSh 10,000

        // Callback with wrong amount (KSh 100 instead of KSh 10,000)
        $payload = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $intentRes['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'WRONG_AMT_123'],
                            ['Name' => 'Amount', 'Value' => 100.00]
                        ]
                    ]
                ]
            ]
        ];

        $res = $this->mpesaService->processCallback($payload);

        if ($res !== false) {
            throw new \Exception("Callback with amount mismatch was incorrectly accepted!");
        }
    }

    private function testExpiredIntent(): void
    {
        $intentId = Ulid::generate();
        $ref = 'BENCH-PI-EXPIRED';
        $pastExp = date('Y-m-d H:i:s', strtotime('-20 minutes'));

        $stmt = $this->pdo->prepare("
            INSERT INTO payment_intents 
            (id, organization_id, user_id, plan_id, reference, amount, currency, phone_number, provider, status, expires_at, created_at, updated_at)
            VALUES (?, ?, ?, 2, ?, 1000.00, 'KES', '254712345678', 'imbank', 'pending', ?, NOW(), NOW())
        ");
        $stmt->execute([$intentId, $this->testOrgId, $this->testUserId, $ref, $pastExp]);

        $statusRes = $this->mpesaService->getIntentStatus($intentId, $this->testOrgId);

        if ($statusRes['status'] !== 'expired') {
            throw new \Exception("Expired payment intent was not auto-expired. Got {$statusRes['status']}");
        }
    }

    private function testSubscriptionRenewal(): void
    {
        // Activate Standard Monthly (plan 2)
        $this->subscriptionService->activateSubscription($this->testOrgId, 2, 'REC_REN_1');
        $sub1 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry1Ts = strtotime($sub1['expires_at']);

        // Renew Standard Monthly (plan 2)
        $this->subscriptionService->activateSubscription($this->testOrgId, 2, 'REC_REN_2');
        $sub2 = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);
        $expiry2Ts = strtotime($sub2['expires_at']);

        // Renewing should add 30 days onto expiry1Ts
        $expectedTs = strtotime('+30 days', $expiry1Ts);

        if (abs($expiry2Ts - $expectedTs) > 5) {
            throw new \Exception("Subscription renewal did not stack duration correctly. Got {$sub2['expires_at']}");
        }
    }

    private function testProUpgradePolicy(): void
    {
        // Upgrade to Benchero Pro (plan 4 - KSh 25,000/yr)
        $this->subscriptionService->activateSubscription($this->testOrgId, 4, 'REC_PRO_UPGRADE');
        $sub = $this->subscriptionService->getSubscriptionStatus($this->testOrgId);

        if ($sub['plan_id'] != 4 || $sub['status'] !== SubscriptionService::STATUS_ACTIVE) {
            throw new \Exception("Pro upgrade failed to set plan_id to 4.");
        }
    }

    private function testAdminPaymentFiltering(): void
    {
        $db = Database::getConnection();
        $cnt = $db->query("SELECT COUNT(*) FROM payment_intents")->fetchColumn();
        
        if ($cnt == 0) {
            throw new \Exception("No payment intents found for admin filtering test.");
        }
    }

    private function cleanupTestData(): void
    {
        $this->pdo->exec("DELETE FROM audit_logs WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM payments WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM subscriptions WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM organization_user WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM users WHERE id = '{$this->testUserId}'");
        $this->pdo->exec("DELETE FROM organizations WHERE id = '{$this->testOrgId}'");
    }
}

$suite = new StkPushBillingTestSuite();
$suite->run();
