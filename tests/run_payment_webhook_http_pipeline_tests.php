<?php

/**
 * Benchero — Payment Webhook HTTP Pipeline & CSRF Verification Test Suite
 *
 * Exercises the actual HTTP request pipeline to verify:
 * 1. POST /billing/imbank/callback without CSRF reaches callback controller (not 403)
 *    and rejects invalid/unauthenticated payloads.
 * 2. POST /billing/paypal/webhook without CSRF reaches webhook controller (not 403)
 *    and rejects invalid/unauthenticated payloads.
 * 3. Browser-facing billing POSTs without CSRF are rejected with 403.
 * 4. Browser-facing billing POSTs with valid CSRF proceed past CsrfMiddleware.
 * 5. Strict path matching prevents prefix or wildcard bypasses.
 * 6. Duplicate callback notifications are idempotent.
 * 7. Fake/tampered callback data never activates real subscriptions.
 */

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\SubscriptionService;

class PaymentWebhookHttpPipelineTestSuite
{
    private \PDO $pdo;
    private string $baseUrl;
    private int $passed = 0;
    private int $failed = 0;
    private string $testOrgId;
    private string $testOrgSlug;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
        $this->baseUrl = 'http://localhost/benchero';
        $this->testOrgId = Ulid::generate();
        $this->testOrgSlug = 'webhook-qa-club-' . time();
        $this->setupTestData();
    }

    private function setupTestData(): void
    {
        $stmt = $this->pdo->prepare("
            INSERT INTO organizations (id, name, slug, country, timezone, created_at, updated_at)
            VALUES (?, 'Webhook QA Club', ?, 'KE', 'Africa/Nairobi', NOW(), NOW())
        ");
        $stmt->execute([$this->testOrgId, $this->testOrgSlug]);

        $sub = new SubscriptionService();
        $sub->initializeTrialSubscription($this->testOrgId);
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

    private function httpRequest(string $method, string $path, array $data = [], array $headers = [], ?string $cookieFile = null): array
    {
        $url = $this->baseUrl . $path;
        $ch = curl_init($url);

        $curlHeaders = [];
        $isJson = false;
        foreach ($headers as $k => $v) {
            $curlHeaders[] = "$k: $v";
            if (stripos($k, 'content-type') !== false && stripos($v, 'application/json') !== false) {
                $isJson = true;
            }
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));

        if (!empty($data) || $method === 'POST') {
            if ($isJson) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        if (!empty($curlHeaders)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        }

        if ($cookieFile !== null) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
        }

        $rawResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        curl_close($ch);

        $headerStr = substr($rawResponse, 0, $headerSize);
        $bodyStr = substr($rawResponse, $headerSize);

        $json = json_decode($bodyStr, true);

        return [
            'code' => $httpCode,
            'headers' => $headerStr,
            'body' => $bodyStr,
            'json' => $json
        ];
    }

    public function run(): void
    {
        echo "=====================================================\n";
        echo " PAYMENT WEBHOOK HTTP PIPELINE & CSRF TEST SUITE\n";
        echo "=====================================================\n\n";

        $this->testImBankCallbackWithoutCsrfReachesController();
        $this->testImBankCallbackRejectsMalformedData();
        $this->testImBankCallbackRejectsUnauthenticatedIntent();
        $this->testPayPalWebhookWithoutCsrfReachesController();
        $this->testPayPalWebhookRejectsMalformedData();
        $this->testPayPalWebhookRejectsUnauthenticatedPayload();
        $this->testBrowserBillingPostWithoutCsrfBlocked();
        $this->testBrowserBillingPostWithValidCsrfPassesMiddleware();
        $this->testStrictPathExemptionNoWildcardLeak();
        $this->testWebhookIdempotencyPreserved();
        $this->testFakeCallbackCannotActivateSubscription();

        $this->cleanupTestData();

        echo "\n=====================================================\n";
        echo " SUMMARY: Passed {$this->passed} / Failed {$this->failed}\n";
        echo "=====================================================\n";

        if ($this->failed > 0) {
            exit(1);
        }
    }

    public function testImBankCallbackWithoutCsrfReachesController(): void
    {
        $res = $this->httpRequest('POST', '/billing/imbank/callback', [], ['Content-Type' => 'application/json']);
        $this->assert($res['code'] !== 403, "POST /billing/imbank/callback is NOT rejected with 403 Forbidden");
        $this->assert($res['code'] === 400, "Empty payload reaches controller and returns 400 Bad Request");
        $this->assert(isset($res['json']['ResultCode']) && $res['json']['ResultCode'] === 1, "Controller returned ResultCode 1 for invalid payload");
    }

    public function testImBankCallbackRejectsMalformedData(): void
    {
        $res = $this->httpRequest('POST', '/billing/imbank/callback', ['broken' => 'payload'], ['Content-Type' => 'application/json']);
        $this->assert($res['code'] === 200, "Malformed payload processed through controller");
        $this->assert($res['json']['ResultDesc'] === 'Callback Processed', "Controller acknowledged callback without marking as Success");

        // Verify no payment was created
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE organization_id = ?");
        $stmt->execute([$this->testOrgId]);
        $count = (int)$stmt->fetchColumn();
        $this->assert($count === 0, "No payment created from malformed I&M callback");
    }

    public function testImBankCallbackRejectsUnauthenticatedIntent(): void
    {
        // Try to fake a successful callback with non-existent CheckoutRequestID
        $fakeCallback = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => 'CHK-NONEXISTENT-' . time(),
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'REC_FAKE_' . time()],
                            ['Name' => 'Amount', 'Value' => 25000.0]
                        ]
                    ]
                ]
            ]
        ];

        $res = $this->httpRequest('POST', '/billing/imbank/callback', $fakeCallback, ['Content-Type' => 'application/json']);
        $this->assert($res['code'] === 200, "HTTP pipeline delivered callback to controller");
        $this->assert($res['json']['ResultDesc'] === 'Callback Processed', "Non-existent intent was rejected by service (not Success)");

        // Verify test organization subscription is still trial
        $sub = (new SubscriptionService())->getSubscriptionStatus($this->testOrgId);
        $this->assert($sub['status'] === SubscriptionService::STATUS_TRIAL, "Organization subscription remains in trial (unaffected)");
    }

    public function testPayPalWebhookWithoutCsrfReachesController(): void
    {
        $res = $this->httpRequest('POST', '/billing/paypal/webhook', [], ['Content-Type' => 'application/json']);
        $this->assert($res['code'] !== 403, "POST /billing/paypal/webhook is NOT rejected with 403 Forbidden");
        $this->assert($res['code'] === 400, "Empty payload reaches controller and returns 400 Bad Request");
        $this->assert(($res['json']['message'] ?? '') === 'Invalid JSON payload', "Controller returned invalid JSON error");
    }

    public function testPayPalWebhookRejectsMalformedData(): void
    {
        $res = $this->httpRequest('POST', '/billing/paypal/webhook', ['invalid' => 'structure'], ['Content-Type' => 'application/json']);
        $this->assert($res['code'] === 200, "Controller returned HTTP 200 acknowledgment");
        $this->assert($res['json']['processed'] === false, "PayPal verification rejected malformed payload (processed = false)");
    }

    public function testPayPalWebhookRejectsUnauthenticatedPayload(): void
    {
        $fakeWebhook = [
            'event_type' => 'PAYMENT.CAPTURE.COMPLETED',
            'resource' => [
                'id' => 'CAP_FAKE_' . time(),
                'amount' => ['value' => '200.00', 'currency_code' => 'USD'],
                'custom_id' => 'ORDER-NONEXISTENT-' . time()
            ]
        ];

        $res = $this->httpRequest('POST', '/billing/paypal/webhook', $fakeWebhook, ['Content-Type' => 'application/json']);
        $this->assert($res['code'] === 200, "PayPal webhook HTTP pipeline responded 200");
        $this->assert($res['json']['processed'] === false, "Unauthenticated / non-existent intent rejected (processed = false)");

        // Verify test organization subscription is still trial
        $sub = (new SubscriptionService())->getSubscriptionStatus($this->testOrgId);
        $this->assert($sub['status'] === SubscriptionService::STATUS_TRIAL, "Organization subscription remains in trial");
    }

    public function testBrowserBillingPostWithoutCsrfBlocked(): void
    {
        $routes = [
            "/o/{$this->testOrgSlug}/billing/payment-intent",
            "/o/{$this->testOrgSlug}/billing/payment-intent/PI-FAKE123/initiate",
            "/o/{$this->testOrgSlug}/billing/stkpush",
            "/o/{$this->testOrgSlug}/billing/paypal/create-order",
            "/o/{$this->testOrgSlug}/billing/paypal/capture-order",
        ];

        foreach ($routes as $route) {
            $res = $this->httpRequest('POST', $route, ['plan_id' => 4]);
            $this->assert($res['code'] === 403, "Browser route {$route} without CSRF returns 403 Forbidden");
            $this->assert(str_contains($res['body'], 'CSRF token mismatch'), "Response contains CSRF token mismatch message");
        }
    }

    public function testBrowserBillingPostWithValidCsrfPassesMiddleware(): void
    {
        $cookieFile = tempnam(sys_get_temp_dir(), 'benchero_cookie_');

        // 1. Fetch login page to initiate session and extract CSRF token
        $loginRes = $this->httpRequest('GET', '/login', [], [], $cookieFile);

        preg_match('/name="_csrf" value="([^"]+)"/', $loginRes['body'], $csrfMatches);
        $csrfToken = $csrfMatches[1] ?? '';
        $this->assert(!empty($csrfToken), "CSRF token extracted from HTML form: " . substr($csrfToken, 0, 8) . '...');

        // 2. POST to browser billing endpoint with valid CSRF token and session cookie
        $postRes = $this->httpRequest(
            'POST',
            "/o/{$this->testOrgSlug}/billing/payment-intent",
            ['_csrf' => $csrfToken, 'plan_id' => 4],
            ['Content-Type' => 'application/x-www-form-urlencoded'],
            $cookieFile
        );

        @unlink($cookieFile);

        // It must NOT be rejected with 403 CSRF mismatch
        $this->assert($postRes['code'] !== 403, "POST with valid CSRF is NOT rejected with 403 Forbidden (Got HTTP {$postRes['code']})");
        $this->assert(!str_contains($postRes['body'], 'CSRF token mismatch'), "No CSRF mismatch error in response");
    }

    public function testStrictPathExemptionNoWildcardLeak(): void
    {
        // Verify that prefix attacks like /billing/imbank/callback_fake or /billing/paypal/webhook_fake are 403
        $leakPaths = [
            '/billing/imbank/callback_fake',
            '/billing/imbank/callback/child',
            '/billing/paypal/webhook_fake',
            '/billing/paypal/webhook/child',
            '/billing/other'
        ];

        foreach ($leakPaths as $path) {
            $res = $this->httpRequest('POST', $path, ['data' => 1]);
            $this->assert($res['code'] === 403, "Tampered path {$path} is rejected by CsrfMiddleware with 403");
        }
    }

    public function testWebhookIdempotencyPreserved(): void
    {
        // Create an intent in the database for our test org
        $paymentService = new \Benchero\Services\PaymentService($this->pdo);
        $intent = $paymentService->createPaymentIntent(
            $this->testOrgId,
            null,
            5,
            'mpesa',
            ['phone_number' => '0712345678']
        );

        $receipt = 'HTTP_IDEM_' . time();
        $validCallback = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $intent['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                            ['Name' => 'Amount', 'Value' => 2500.0]
                        ]
                    ]
                ]
            ]
        ];

        // 1st HTTP callback
        $res1 = $this->httpRequest('POST', '/billing/imbank/callback', $validCallback, ['Content-Type' => 'application/json']);
        $this->assert($res1['code'] === 200, "First HTTP callback returned 200");
        $this->assert($res1['json']['ResultDesc'] === 'Success', "First HTTP callback successfully processed");

        // Verify 1 payment record exists for this receipt
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM payments WHERE mpesa_receipt_number = ?");
        $stmt->execute([$receipt]);
        $this->assert((int)$stmt->fetchColumn() === 1, "Exactly 1 payment record created for receipt {$receipt}");

        $sub1 = (new SubscriptionService())->getSubscriptionStatus($this->testOrgId);
        $expiry1 = $sub1['expires_at'];

        // 2nd HTTP callback (duplicate replay)
        $res2 = $this->httpRequest('POST', '/billing/imbank/callback', $validCallback, ['Content-Type' => 'application/json']);
        $this->assert($res2['code'] === 200, "Second duplicate HTTP callback returned 200");

        // Verify count remains 1 and expiry is identical
        $stmt->execute([$receipt]);
        $this->assert((int)$stmt->fetchColumn() === 1, "Duplicate callback did NOT create duplicate payment record");

        $sub2 = (new SubscriptionService())->getSubscriptionStatus($this->testOrgId);
        $expiry2 = $sub2['expires_at'];
        $this->assert($expiry1 === $expiry2, "Duplicate callback did NOT double-extend subscription duration");
    }

    public function testFakeCallbackCannotActivateSubscription(): void
    {
        // Create an intent with amount KSh 10,000
        $paymentService = new \Benchero\Services\PaymentService($this->pdo);
        $intent = $paymentService->createPaymentIntent(
            $this->testOrgId,
            null,
            3, // Standard Yearly: 10,000 KES
            'mpesa',
            ['phone_number' => '0712345678']
        );

        // Attempt tampered amount: KSh 10 instead of KSh 10,000
        $tamperedCallback = [
            'Body' => [
                'stkCallback' => [
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CheckoutRequestID' => $intent['reference'],
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'TAMPERED_' . time()],
                            ['Name' => 'Amount', 'Value' => 10.0]
                        ]
                    ]
                ]
            ]
        ];

        $res = $this->httpRequest('POST', '/billing/imbank/callback', $tamperedCallback, ['Content-Type' => 'application/json']);
        $this->assert($res['code'] === 200, "HTTP pipeline delivered tampered callback to controller");
        $this->assert($res['json']['ResultDesc'] === 'Callback Processed', "Tampered amount was rejected (not Success)");

        // Verify intent status is NOT completed
        $checkStmt = $this->pdo->prepare("SELECT status FROM payment_intents WHERE id = ?");
        $checkStmt->execute([$intent['id']]);
        $status = $checkStmt->fetchColumn();
        $this->assert($status !== 'completed', "Intent status was NOT marked completed with tampered amount");
    }

    private function cleanupTestData(): void
    {
        $this->pdo->exec("DELETE FROM payments WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM payment_intents WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM subscriptions WHERE organization_id = '{$this->testOrgId}'");
        $this->pdo->exec("DELETE FROM organizations WHERE id = '{$this->testOrgId}'");
    }
}

$suite = new PaymentWebhookHttpPipelineTestSuite();
$suite->run();
