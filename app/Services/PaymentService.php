<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Core\PricingConfig;
use Benchero\Services\Gateways\PaymentGatewayFactory;
use Benchero\Services\Gateways\ImBankPaymentGateway;
use Benchero\Services\Gateways\PayPalPaymentGateway;
use PDO;
use Exception;

class PaymentService
{
    private PDO $db;
    private SubscriptionService $subscriptionService;

    public function __construct(?PDO $db = null, ?SubscriptionService $subscriptionService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->subscriptionService = $subscriptionService ?? new SubscriptionService();
    }

    /**
     * Map server-side plan IDs to official canonical pricing (in KES).
     * Grounds directly in database plans table with reliable fallback.
     */
    public function getServerPlanPrice(int $planId): ?float
    {
        try {
            $stmt = $this->db->prepare("SELECT price_kes FROM plans WHERE id = ? AND deleted_at IS NULL LIMIT 1");
            $stmt->execute([$planId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row !== false && isset($row['price_kes'])) {
                return (float)$row['price_kes'];
            }
        } catch (\Throwable $e) {}

        return match ($planId) {
            1 => 0.0,
            2 => 1000.0,
            3 => 10000.0,
            4 => 20000.0,
            5 => 2500.0,
            default => null,
        };
    }

    /**
     * Create a pending payment intent record for either M-Pesa or PayPal.
     *
     * @param string $orgId Organization ID
     * @param string|null $userId User ID
     * @param int $planId Plan ID
     * @param string $method Payment method ('mpesa', 'paypal', 'card')
     * @param array $options Additional options (phone_number, currency, custom_amount, return_url, cancel_url)
     * @return array
     */
    public function createPaymentIntent(string $orgId, ?string $userId, int $planId, string $method = 'mpesa', array $options = []): array
    {
        $serverCanonicalAmount = $this->getServerPlanPrice($planId);
        if ($serverCanonicalAmount === null) {
            return [
                'success' => false,
                'error' => 'Invalid or unknown subscription plan selected.'
            ];
        }

        $method = strtolower(trim($method));
        $isPayPal = in_array($method, ['paypal', 'card', 'credit_card', 'debit_card'], true);

        $normalizedPhone = null;
        $chargedAmount = $serverCanonicalAmount;
        $chargedCurrency = 'KES';
        $baseAmount = $serverCanonicalAmount;
        $baseCurrency = 'KES';
        $exchangeRate = null;
        $provider = 'imbank';

        if ($isPayPal) {
            $provider = 'paypal';
            $method = ($method === 'card') ? 'card' : 'paypal';
            $requestedCurrency = strtoupper(trim((string)($options['currency'] ?? env('PAYPAL_CURRENCY', 'USD'))));
            if (!in_array($requestedCurrency, ['USD', 'EUR', 'GBP'], true)) {
                return [
                    'success' => false,
                    'error' => "Unsupported currency: {$requestedCurrency}. Supported international currencies are USD, EUR, GBP."
                ];
            }
            $intlPricing = PricingConfig::getInternationalPrice($planId, $requestedCurrency);

            $chargedAmount = (float)$intlPricing['charged_amount'];
            $chargedCurrency = $intlPricing['charged_currency'];
            $baseAmount = (float)$intlPricing['canonical_amount'];
            $baseCurrency = $intlPricing['canonical_currency'];
            $exchangeRate = $intlPricing['exchange_rate'] ?? null;

            $customAmount = isset($options['custom_amount']) ? (float)$options['custom_amount'] : null;
            if ($customAmount !== null && abs($customAmount - $chargedAmount) > 0.01) {
                return [
                    'success' => false,
                    'error' => 'Submitted payment amount does not match official plan pricing.'
                ];
            }
        } else {
            // M-Pesa STK Push
            if (isset($options['currency']) && strtoupper(trim((string)$options['currency'])) !== 'KES') {
                return [
                    'success' => false,
                    'error' => 'M-Pesa payment gateway only supports transactions in KES (Kenyan Shillings).'
                ];
            }
            $provider = 'imbank';
            $method = 'mpesa';
            $phone = (string)($options['phone_number'] ?? $options['phone'] ?? '');
            $normalizedPhone = ImBankPaymentGateway::normalizePhoneNumber($phone);
            if (!$normalizedPhone) {
                return [
                    'success' => false,
                    'error' => 'Invalid phone number. Must be a valid Kenyan mobile number (e.g., 0712345678 or 254712345678).'
                ];
            }

            $customAmount = isset($options['custom_amount']) ? (float)$options['custom_amount'] : null;
            if ($customAmount !== null && abs($customAmount - $serverCanonicalAmount) > 0.01) {
                return [
                    'success' => false,
                    'error' => 'Submitted payment amount does not match official plan pricing.'
                ];
            }
        }

        $intentId = Ulid::generate();
        $paymentIntentId = 'PI-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));
        $reference = 'CHK-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));

        $stmt = $this->db->prepare("
            INSERT INTO payment_intents (
                id, organization_id, user_id, payment_intent_id, reference, plan_id,
                amount, currency, base_amount, base_currency, exchange_rate,
                phone_number, provider, payment_method, status, expires_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', DATE_ADD(NOW(), INTERVAL 15 MINUTE), NOW(), NOW())
        ");
        $stmt->execute([
            $intentId,
            $orgId,
            $userId,
            $paymentIntentId,
            $reference,
            $planId,
            $chargedAmount,
            $chargedCurrency,
            $baseAmount,
            $baseCurrency,
            $exchangeRate,
            $normalizedPhone,
            $provider,
            $method
        ]);

        return [
            'success' => true,
            'id' => $intentId,
            'intent_id' => $intentId,
            'organization_id' => $orgId,
            'user_id' => $userId,
            'plan_id' => $planId,
            'payment_intent_id' => $paymentIntentId,
            'reference' => $reference,
            'amount' => (float)$chargedAmount,
            'currency' => $chargedCurrency,
            'base_amount' => (float)$baseAmount,
            'base_currency' => $baseCurrency,
            'exchange_rate' => $exchangeRate !== null ? (float)$exchangeRate : null,
            'phone_number' => $normalizedPhone,
            'provider' => $provider,
            'payment_method' => $method,
            'status' => 'pending'
        ];
    }

    /**
     * Retrieve payment intent status and details.
     */
    public function getIntentStatus(string $intentId, ?string $orgId = null): array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_intents WHERE id = ? OR payment_intent_id = ? OR reference = ? OR provider_reference = ? LIMIT 1");
        $stmt->execute([$intentId, $intentId, $intentId, $intentId]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent) {
            throw new Exception("Payment intent not found.");
        }

        // Auto-expire intents older than 15 minutes or past expires_at if still pending/initiated
        if (in_array($intent['status'], ['pending', 'initiated'], true)) {
            $createdTime = strtotime($intent['created_at']);
            $expiresTime = !empty($intent['expires_at']) ? strtotime($intent['expires_at']) : null;

            if (($expiresTime && $expiresTime <= time()) || (time() - $createdTime > 900)) {
                $up = $this->db->prepare("UPDATE payment_intents SET status = 'expired', updated_at = NOW() WHERE id = ?");
                $up->execute([$intent['id']]);
                $intent['status'] = 'expired';
            }
        }

        $intent['amount'] = (float)$intent['amount'];
        $intent['base_amount'] = isset($intent['base_amount']) ? (float)$intent['base_amount'] : (float)$intent['amount'];
        $intent['intent_id'] = $intent['id'];
        $intent['success'] = true;

        return $intent;
    }

    /**
     * Initiate payment request via appropriate gateway adapter.
     */
    public function initiatePayment(string $intentIdOrOrgId, array $params = []): array
    {
        $intent = null;
        try {
            $intent = $this->getIntentStatus($intentIdOrOrgId);
        } catch (\Throwable $e) {
            $intent = null;
        }

        if (!$intent) {
            $planId = (int)($params['plan_id'] ?? 4);
            $method = (string)($params['payment_method'] ?? 'mpesa');
            $created = $this->createPaymentIntent($intentIdOrOrgId, null, $planId, $method, $params);
            if (!($created['success'] ?? false)) {
                return $created;
            }
            $intent = $this->getIntentStatus($created['intent_id']);
        }

        if (in_array($intent['status'], ['completed', 'failed', 'cancelled', 'expired'], true)) {
            throw new Exception("Payment intent is already in terminal state: " . $intent['status']);
        }

        $provider = $intent['provider'] ?? 'imbank';
        $gateway = PaymentGatewayFactory::create($provider);

        // Fetch plan name for description
        $plan = $this->subscriptionService->getPlan($intent['plan_id']);
        $planName = $plan['name'] ?? 'Benchero Subscription';

        $gatewayPayload = [
            'intent_id' => $intent['id'],
            'payment_intent_id' => $intent['payment_intent_id'] ?? $intent['reference'],
            'reference' => $intent['reference'],
            'amount' => (float)$intent['amount'],
            'currency' => $intent['currency'] ?? 'KES',
            'phone_number' => $intent['phone_number'] ?? ($params['phone_number'] ?? null),
            'organization_id' => $intent['organization_id'],
            'plan_name' => $planName,
            'return_url' => $params['return_url'] ?? (env('APP_URL') . '/billing/paypal/return'),
            'cancel_url' => $params['cancel_url'] ?? (env('APP_URL') . '/billing/paypal/cancel')
        ];

        // Specific test mode handling for M-Pesa mock phone (supports both localhost and production for test accounts)
        if ($provider === 'imbank' && !empty($gatewayPayload['phone_number'])) {
            $isMockTestPhone = ($gatewayPayload['phone_number'] === '254712345678' || $gatewayPayload['phone_number'] === '0712345678');
            $isTestAccount = is_test_account() || is_test_account($gatewayPayload['organization_id'] ?? null);

            if ((env('APP_ENV') === 'testing' && $isMockTestPhone) || ($isTestAccount && $isMockTestPhone)) {
                $receipt = 'REC_MOCK_' . rand(100000, 999999);
                $this->processWebhook('imbank', [
                    'Body' => [
                        'stkCallback' => [
                            'ResultCode' => 0,
                            'ResultDesc' => 'Success',
                            'CheckoutRequestID' => $intent['reference'],
                            'CallbackMetadata' => [
                                'Item' => [
                                    ['Name' => 'MpesaReceiptNumber', 'Value' => $receipt],
                                    ['Name' => 'Amount', 'Value' => (float)$intent['amount']]
                                ]
                            ]
                        ]
                    ]
                ]);

                return [
                    'status' => 'completed_mock',
                    'success' => true,
                    'intent_id' => $intent['id'],
                    'reference' => $intent['reference'],
                    'receipt' => $receipt,
                    'message' => 'Test account mock payment processed successfully.'
                ];
            }
        }

        $result = $gateway->initiatePayment($gatewayPayload);

        $newStatus = ($result['success'] ?? false) ? 'initiated' : 'failed';
        $providerRef = $result['provider_reference'] ?? ($result['order_id'] ?? null);

        $up = $this->db->prepare("
            UPDATE payment_intents 
            SET provider_reference = COALESCE(?, provider_reference), status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([$providerRef, $newStatus, $intent['id']]);

        return array_merge($result, [
            'status' => $newStatus,
            'success' => $result['success'] ?? false,
            'intent_id' => $intent['id'],
            'reference' => $intent['reference'] ?? $intent['payment_intent_id'],
            'provider_reference' => $providerRef
        ]);
    }

    /**
     * Capture PayPal order server-side and activate subscription.
     */
    public function capturePayPalPayment(string $intentId, string $paypalOrderId): array
    {
        $intent = $this->getIntentStatus($intentId);

        if ($intent['status'] === 'completed') {
            return [
                'success' => true,
                'status' => 'completed',
                'message' => 'Payment already completed and subscription active.'
            ];
        }

        /** @var PayPalPaymentGateway $gateway */
        $gateway = PaymentGatewayFactory::create('paypal');
        $captureRes = $gateway->capturePayment($paypalOrderId);

        if (!($captureRes['success'] ?? false) || ($captureRes['status'] ?? '') !== 'completed') {
            $up = $this->db->prepare("UPDATE payment_intents SET status = 'failed', result_desc = ?, updated_at = NOW() WHERE id = ?");
            $up->execute([$captureRes['message'] ?? 'PayPal capture failed', $intent['id']]);

            return [
                'success' => false,
                'status' => 'failed',
                'message' => $captureRes['message'] ?? 'Failed to capture PayPal payment.'
            ];
        }

        // Amount verification
        $capturedAmount = $captureRes['amount'] ?? null;
        if ($capturedAmount !== null && abs((float)$capturedAmount - (float)$intent['amount']) > 0.05) {
            return [
                'success' => false,
                'status' => 'failed',
                'message' => 'Captured amount does not match intent amount.'
            ];
        }

        // Record successful payment and activate subscription
        $normalizedData = [
            'receipt' => $captureRes['receipt'] ?? $captureRes['capture_id'] ?? $paypalOrderId,
            'provider_reference' => $paypalOrderId,
            'amount' => (float)($captureRes['amount'] ?? $intent['amount']),
            'currency' => $captureRes['currency'] ?? $intent['currency'] ?? 'USD',
            'payer_email' => $captureRes['payer_email'] ?? null,
            'payer_id' => $captureRes['payer_id'] ?? null,
            'payment_method' => $intent['payment_method'] ?? 'paypal',
            'result_code' => 0,
            'result_desc' => 'PayPal payment captured successfully'
        ];

        return $this->recordSuccessfulPayment($intent, $normalizedData);
    }

    /**
     * Process asynchronous server-to-server webhook from any payment provider.
     */
    public function processWebhook(string $provider, array $payload, array $headers = []): bool
    {
        $provider = strtolower(trim($provider));
        $gateway = PaymentGatewayFactory::create($provider);

        $norm = $gateway->verifyPaymentNotification($payload, $headers);
        if (!($norm['valid'] ?? false)) {
            return false;
        }

        if (($norm['status'] ?? '') === 'ignored') {
            return true;
        }

        $providerRef = $norm['provider_reference'] ?? null;
        $receipt = $norm['receipt'] ?? null;
        $status = $norm['status'] ?? 'pending';

        if (!$providerRef && !$receipt) {
            return false;
        }

        // Find matching payment intent
        $stmt = $this->db->prepare("
            SELECT * FROM payment_intents 
            WHERE provider_reference = ? OR reference = ? OR payment_intent_id = ? OR id = ? OR mpesa_receipt_number = ?
            LIMIT 1
        ");
        $stmt->execute([$providerRef, $providerRef, $providerRef, $providerRef, $receipt]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent) {
            return false;
        }

        // Amount validation: reject if callback amount is provided and doesn't match intent amount
        $cbAmount = $norm['amount'] ?? null;
        if ($cbAmount !== null && $status === 'completed') {
            if (abs((float)$cbAmount - (float)$intent['amount']) > 0.05) {
                return false;
            }
        }

        // Idempotency: if already completed, return true safely
        if ($intent['status'] === 'completed') {
            return true;
        }

        if ($status === 'completed') {
            $normalizedData = [
                'receipt' => $receipt ?? ('REC-' . time()),
                'provider_reference' => $providerRef ?? $intent['reference'],
                'amount' => $norm['amount'] ?? (float)$intent['amount'],
                'currency' => $norm['currency'] ?? $intent['currency'] ?? 'KES',
                'payer_email' => $norm['payer_email'] ?? null,
                'payer_id' => $norm['payer_id'] ?? null,
                'payment_method' => $intent['payment_method'] ?? 'mpesa',
                'result_code' => 0,
                'result_desc' => $norm['message'] ?? 'Payment processed successfully'
            ];

            $res = $this->recordSuccessfulPayment($intent, $normalizedData);
            return $res['success'] ?? false;
        }

        // Handle failed or cancelled
        $finalStatus = ($status === 'cancelled') ? 'cancelled' : 'failed';
        $up = $this->db->prepare("
            UPDATE payment_intents 
            SET status = ?, result_desc = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([$finalStatus, $norm['message'] ?? 'Payment failed', $intent['id']]);

        return true;
    }

    /**
     * Atomically record successful payment in payments table and activate organization subscription.
     */
    public function recordSuccessfulPayment(array $intent, array $normalizedData): array
    {
        $intentId = $intent['id'] ?? $intent['intent_id'] ?? null;
        if ((empty($intent['organization_id']) || empty($intent['id'])) && $intentId) {
            $intent = $this->getIntentStatus($intentId);
        }

        $receipt = $normalizedData['receipt'] ?? null;
        $providerRef = $normalizedData['provider_reference'] ?? $intent['provider_reference'] ?? null;

        // Idempotency: verify if this payment or intent was already finalized
        $checkStmt = $this->db->prepare("
            SELECT id, mpesa_receipt_number FROM payments 
            WHERE payment_intent_id = ? 
               OR (mpesa_receipt_number IS NOT NULL AND mpesa_receipt_number = ?)
               OR (provider_reference IS NOT NULL AND provider_reference = ?)
            LIMIT 1
        ");
        $checkStmt->execute([$intent['id'], $receipt, $providerRef]);
        $existingPayment = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($existingPayment || ($intent['status'] === 'completed')) {
            return [
                'success' => true,
                'status' => 'completed',
                'payment_id' => $existingPayment['id'] ?? null,
                'receipt' => $existingPayment['mpesa_receipt_number'] ?? $receipt,
                'provider' => $intent['provider'] ?? 'imbank',
                'message' => 'Payment already completed and subscription active.'
            ];
        }

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }

        try {
            // Update payment intent
            $up = $this->db->prepare("
                UPDATE payment_intents 
                SET status = 'completed', 
                    result_code = ?, 
                    result_desc = ?, 
                    mpesa_receipt_number = ?,
                    payer_email = COALESCE(?, payer_email),
                    payer_id = COALESCE(?, payer_id),
                    provider_reference = COALESCE(?, provider_reference),
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $up->execute([
                $normalizedData['result_code'] ?? 0,
                $normalizedData['result_desc'] ?? 'Success',
                $normalizedData['receipt'] ?? null,
                $normalizedData['payer_email'] ?? null,
                $normalizedData['payer_id'] ?? null,
                $normalizedData['provider_reference'] ?? null,
                $intent['id']
            ]);

            // Create unified payment record in payments table
            $paymentId = Ulid::generate();
            $receipt = $normalizedData['receipt'] ?? ('REC-' . time());
            $provider = $intent['provider'] ?? 'imbank';
            $paymentMethod = $normalizedData['payment_method'] ?? $intent['payment_method'] ?? 'mpesa';

            $metadata = json_encode([
                'intent_id' => $intent['id'],
                'phone' => $intent['phone_number'] ?? null,
                'payer_email' => $normalizedData['payer_email'] ?? null,
                'payer_id' => $normalizedData['payer_id'] ?? null,
                'base_amount' => $intent['base_amount'] ?? $intent['amount'],
                'base_currency' => $intent['base_currency'] ?? 'KES',
                'exchange_rate' => $intent['exchange_rate'] ?? 1.0
            ]);

            $payStmt = $this->db->prepare("
                INSERT INTO payments (
                    id, organization_id, user_id, payment_intent_id, amount, currency,
                    base_amount, base_currency, exchange_rate, status, payment_method,
                    provider, mpesa_receipt_number, provider_reference, payer_email, payer_id,
                    metadata, created_at, updated_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'completed', ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
            ");
            $payStmt->execute([
                $paymentId,
                $intent['organization_id'],
                $intent['user_id'] ?? null,
                $intent['id'],
                $normalizedData['amount'] ?? $intent['amount'],
                $normalizedData['currency'] ?? $intent['currency'] ?? 'KES',
                $intent['base_amount'] ?? $intent['amount'],
                $intent['base_currency'] ?? 'KES',
                $intent['exchange_rate'] ?? 1.0,
                $paymentMethod,
                $provider,
                $receipt,
                $normalizedData['provider_reference'] ?? $intent['provider_reference'] ?? $receipt,
                $normalizedData['payer_email'] ?? null,
                $normalizedData['payer_id'] ?? null,
                $metadata
            ]);

            // Activate subscription through decoupled subscription service
            $this->subscriptionService->activateSubscription(
                $intent['organization_id'],
                (int)$intent['plan_id'],
                $receipt,
                $paymentId,
                $provider
            );

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }

            return [
                'success' => true,
                'status' => 'completed',
                'payment_id' => $paymentId,
                'receipt' => $receipt,
                'provider' => $provider,
                'message' => 'Payment confirmed and subscription activated successfully.'
            ];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Admin manual reconciliation of payment.
     */
    public function reconcilePayment(string $intentRef, string $receipt, string $reconciledBy): bool
    {
        $stmt = $this->db->prepare("
            SELECT * FROM payment_intents 
            WHERE reference = ? OR payment_intent_id = ? OR id = ? OR provider_reference = ?
            LIMIT 1
        ");
        $stmt->execute([$intentRef, $intentRef, $intentRef, $intentRef]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent || $intent['status'] === 'completed') {
            return false;
        }

        $normalizedData = [
            'receipt' => $receipt,
            'provider_reference' => $intent['provider_reference'] ?? $intentRef,
            'amount' => (float)$intent['amount'],
            'currency' => $intent['currency'] ?? 'KES',
            'payment_method' => $intent['payment_method'] ?? 'mpesa',
            'result_code' => 0,
            'result_desc' => 'Admin manual reconciliation by ' . $reconciledBy
        ];

        $res = $this->recordSuccessfulPayment($intent, $normalizedData);
        return $res['success'] ?? false;
    }
}
