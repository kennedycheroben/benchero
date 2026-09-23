<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\Gateways\PaymentGatewayFactory;
use Benchero\Services\Gateways\ImBankPaymentGateway;
use PDO;
use Exception;

class MpesaService
{
    private PDO $db;
    private SubscriptionService $subscriptionService;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->subscriptionService = new SubscriptionService();
    }

    /**
     * Map server-side plan IDs to official pricing (in KES).
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
            4 => 25000.0,
            5 => 2500.0,
            default => null,
        };
    }

    /**
     * Create a pending payment intent record.
     */
    public function createPaymentIntent(string $orgId, ?string $userId, int $planId, string $phone, ?float $customAmount = null): array
    {
        $normalizedPhone = ImBankPaymentGateway::normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            return [
                'success' => false,
                'error' => 'Invalid phone number. Must be a valid Kenyan mobile number (e.g., 0712345678 or 254712345678).'
            ];
        }

        $serverAmount = $this->getServerPlanPrice($planId);
        if ($serverAmount === null) {
            return [
                'success' => false,
                'error' => 'Invalid or unknown plan selected.'
            ];
        }

        if ($customAmount !== null && abs($customAmount - $serverAmount) > 0.01) {
            return [
                'success' => false,
                'error' => 'Submitted payment amount does not match official plan pricing.'
            ];
        }

        $intentId = Ulid::generate();
        $paymentIntentId = 'PI-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));
        $reference = 'CHK-' . strtoupper(substr(md5(uniqid('', true)), 0, 12));

        $stmt = $this->db->prepare("
            INSERT INTO payment_intents (
                id, organization_id, user_id, payment_intent_id, reference, plan_id,
                amount, currency, phone_number, provider, status, expires_at, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'KES', ?, 'imbank', 'pending', DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW(), NOW())
        ");
        $stmt->execute([
            $intentId,
            $orgId,
            $userId,
            $paymentIntentId,
            $reference,
            $planId,
            $serverAmount,
            $normalizedPhone
        ]);

        return [
            'success' => true,
            'intent_id' => $intentId,
            'payment_intent_id' => $paymentIntentId,
            'reference' => $reference,
            'amount' => (float)$serverAmount,
            'phone_number' => $normalizedPhone,
            'status' => 'pending'
        ];
    }

    /**
     * Retrieve payment intent status.
     */
    public function getIntentStatus(string $intentId, ?string $orgId = null): array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_intents WHERE id = ? OR payment_intent_id = ? OR reference = ?");
        $stmt->execute([$intentId, $intentId, $intentId]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent) {
            throw new Exception("Payment intent not found.");
        }

        // Auto-expire intents older than 10 minutes or past expires_at if still pending/initiated
        if (in_array($intent['status'], ['pending', 'initiated'], true)) {
            $createdTime = strtotime($intent['created_at']);
            $expiresTime = !empty($intent['expires_at']) ? strtotime($intent['expires_at']) : null;

            if (($expiresTime && $expiresTime <= time()) || (time() - $createdTime > 600)) {
                $up = $this->db->prepare("UPDATE payment_intents SET status = 'expired', updated_at = NOW() WHERE id = ?");
                $up->execute([$intent['id']]);
                $intent['status'] = 'expired';
            }
        }

        $intent['amount'] = (float)$intent['amount'];
        $intent['intent_id'] = $intent['id'];
        $intent['success'] = true;

        return $intent;
    }

    /**
     * Initiate STK Push via I&M Gateway adapter.
     */
    public function initiateStkPush(string $intentIdOrOrgId, string $phone, ?float $amount = null, ?int $planId = null): array
    {
        $intent = null;
        try {
            $intent = $this->getIntentStatus($intentIdOrOrgId);
        } catch (\Throwable $e) {
            $intent = null;
        }

        if (!$intent) {
            $pId = $planId ?? 2;
            $intentRes = $this->createPaymentIntent($intentIdOrOrgId, null, $pId, $phone, $amount);
            if (!($intentRes['success'] ?? false)) {
                return $intentRes;
            }
            $intent = $this->getIntentStatus($intentRes['intent_id']);
        }

        if (in_array($intent['status'], ['completed', 'failed', 'cancelled', 'expired'], true)) {
            throw new Exception("Payment intent is already in terminal state: " . $intent['status']);
        }

        $normalizedPhone = ImBankPaymentGateway::normalizePhoneNumber($phone);
        if (!$normalizedPhone) {
            return ['success' => false, 'error' => 'Invalid phone number format.'];
        }

        // Check testing mode or mock bypass (supports both localhost and production for test accounts)
        $isMockTestPhone = ($phone === '254712345678' || $phone === '0712345678');
        $orgId = $intent['organization_id'] ?? $intentIdOrOrgId;
        $isTestAccount = is_test_account() || is_test_account($orgId);

        if ((env('APP_ENV') !== 'production' && (env('APP_ENV') === 'testing' || $isMockTestPhone)) 
            || ($isTestAccount && $isMockTestPhone)
            || (env('APP_ENV') === 'testing')) {
            $receipt = 'REC_MOCK_' . rand(100000, 999999);
            $this->processCallback([
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

        $gateway = PaymentGatewayFactory::create('imbank');

        $result = $gateway->initiatePayment([
            'intent_id' => $intent['id'],
            'payment_intent_id' => $intent['payment_intent_id'] ?? $intent['reference'],
            'amount' => (float)$intent['amount'],
            'phone_number' => $normalizedPhone,
            'organization_id' => $intent['organization_id']
        ]);

        $newStatus = ($result['success'] ?? false) ? 'initiated' : 'failed';

        $up = $this->db->prepare("
            UPDATE payment_intents 
            SET phone_number = ?, provider_reference = ?, status = ?, updated_at = NOW() 
            WHERE id = ?
        ");
        $up->execute([
            $normalizedPhone,
            $result['provider_reference'] ?? null,
            $newStatus,
            $intent['id']
        ]);

        return array_merge($result, [
            'status' => $newStatus,
            'success' => true,
            'intent_id' => $intent['id'],
            'reference' => $intent['reference'] ?? $intent['payment_intent_id']
        ]);
    }

    /**
     * Process asynchronous M-PESA Callback from Gateway.
     */
    public function processCallback(array $payload, array $headers = []): bool
    {
        // Handle standard Gateway payload format
        $checkoutId = $payload['Body']['stkCallback']['CheckoutRequestID'] ?? $payload['CheckoutRequestID'] ?? $payload['intent_id'] ?? null;
        $resultCode = $payload['Body']['stkCallback']['ResultCode'] ?? $payload['ResultCode'] ?? $payload['result_code'] ?? 1;
        $resultDesc = $payload['Body']['stkCallback']['ResultDesc'] ?? $payload['ResultDesc'] ?? $payload['result_desc'] ?? 'Unknown';
        
        $receipt = null;
        $callbackAmount = null;

        if (isset($payload['Body']['stkCallback']['CallbackMetadata']['Item'])) {
            foreach ($payload['Body']['stkCallback']['CallbackMetadata']['Item'] as $item) {
                if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
                    $receipt = $item['Value'] ?? null;
                }
                if (($item['Name'] ?? '') === 'Amount') {
                    $callbackAmount = (float)($item['Value'] ?? 0);
                }
            }
        }
        if (!$receipt) {
            $receipt = $payload['mpesa_receipt_number'] ?? $payload['receipt'] ?? null;
        }

        $stmt = $this->db->prepare("
            SELECT * FROM payment_intents 
            WHERE reference = ? OR payment_intent_id = ? OR id = ? OR provider_reference = ? 
            LIMIT 1
        ");
        $stmt->execute([$checkoutId, $checkoutId, $checkoutId, $checkoutId]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent) {
            return false;
        }

        // Amount validation: reject if callback amount is provided and doesn't match intent amount
        if ($callbackAmount !== null && (int)$resultCode === 0) {
            if (abs($callbackAmount - (float)$intent['amount']) > 0.01) {
                return false;
            }
        }

        // Idempotency check: if already completed, return true safely
        if ($intent['status'] === 'completed') {
            return true;
        }

        $finalStatus = match ((int)$resultCode) {
            0 => 'completed',
            1032 => 'cancelled',
            default => 'failed'
        };

        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
        try {
            $up = $this->db->prepare("
                UPDATE payment_intents 
                SET status = ?, result_code = ?, result_desc = ?, mpesa_receipt_number = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $up->execute([
                $finalStatus,
                $resultCode,
                $resultDesc,
                $receipt,
                $intent['id']
            ]);

            if ($finalStatus === 'completed') {
                $paymentId = Ulid::generate();
                $payStmt = $this->db->prepare("
                    INSERT INTO payments (
                        id, organization_id, user_id, amount, currency, status,
                        payment_method, mpesa_receipt_number, provider, provider_reference,
                        metadata, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, 'KES', 'completed', 'mpesa', ?, 'imbank', ?, ?, NOW(), NOW())
                ");
                $payStmt->execute([
                    $paymentId,
                    $intent['organization_id'],
                    $intent['user_id'],
                    $intent['amount'],
                    $receipt ?? ('REC-' . time()),
                    $intent['provider_reference'] ?? $checkoutId,
                    json_encode(['intent_id' => $intent['id'], 'phone' => $intent['phone_number']])
                ]);

                // Activate subscription
                $this->subscriptionService->upgradeSubscription(
                    $intent['organization_id'],
                    (int)$intent['plan_id'],
                    $receipt
                );
            }

            if ($this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
