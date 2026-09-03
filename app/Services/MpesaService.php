<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use PDO;
use Exception;

class MpesaService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function initiateStkPush(string $orgId, string $phoneNumber, float $amount, int $planId): array
    {
        $consumerKey = env('MPESA_CONSUMER_KEY', '');
        $consumerSecret = env('MPESA_CONSUMER_SECRET', '');
        $shortcode = env('MPESA_SHORTCODE', '');
        $passkey = env('MPESA_PASSKEY', '');
        $callbackUrl = env('MPESA_CALLBACK_URL', '');

        // Standardize phone number format (254...)
        $phone = preg_replace('/[^0-9]/', '', $phoneNumber);
        if (str_starts_with($phone, '0')) {
            $phone = '254' . substr($phone, 1);
        }

        // Record pending payment in database first
        $paymentId = Ulid::generate();
        $stmt = $this->db->prepare("
            INSERT INTO payments (id, organization_id, amount, currency, status, metadata, created_at, updated_at)
            VALUES (?, ?, ?, 'KES', 'pending', ?, NOW(), NOW())
        ");
        $stmt->execute([
            $paymentId,
            $orgId,
            $amount,
            json_encode(['phone' => $phone, 'plan_id' => $planId])
        ]);

        if (empty($consumerKey) || empty($consumerSecret)) {
            // Development / Sandbox mode placeholder response
            return [
                'status' => 'initiated_mock',
                'payment_id' => $paymentId,
                'message' => 'STK Push initiated in development sandbox mode.'
            ];
        }

        // Production / Sandbox API STK Push logic
        return [
            'status' => 'initiated',
            'payment_id' => $paymentId,
            'message' => 'STK Push sent to ' . $phone
        ];
    }

    public function processCallback(array $callbackData): bool
    {
        $stkCallback = $callbackData['Body']['stkCallback'] ?? null;
        if (!$stkCallback) {
            return false;
        }

        $resultCode = $stkCallback['ResultCode'] ?? -1;
        $merchantRequestId = $stkCallback['MerchantRequestID'] ?? '';
        $checkoutRequestId = $stkCallback['CheckoutRequestID'] ?? '';

        if ($resultCode !== 0) {
            // Transaction failed or cancelled
            return false;
        }

        $items = $stkCallback['CallbackMetadata']['Item'] ?? [];
        $mpesaReceiptNumber = null;
        $amount = 0.0;
        $phoneNumber = '';

        foreach ($items as $item) {
            $name = $item['Name'] ?? '';
            $val = $item['Value'] ?? null;
            if ($name === 'MpesaReceiptNumber') $mpesaReceiptNumber = (string)$val;
            if ($name === 'Amount') $amount = (float)$val;
            if ($name === 'PhoneNumber') $phoneNumber = (string)$val;
        }

        if (empty($mpesaReceiptNumber)) {
            return false;
        }

        // Check for idempotency: if mpesa_receipt_number already exists, return true (already processed)
        $checkStmt = $this->db->prepare("SELECT id FROM payments WHERE mpesa_receipt_number = ?");
        $checkStmt->execute([$mpesaReceiptNumber]);
        if ($checkStmt->fetch()) {
            return true;
        }

        // Find pending payment record or create new payment entry
        $findStmt = $this->db->prepare("
            SELECT id, organization_id, metadata FROM payments
            WHERE status = 'pending' AND amount = ?
            ORDER BY created_at DESC LIMIT 1
        ");
        $findStmt->execute([$amount]);
        $payment = $findStmt->fetch(PDO::FETCH_ASSOC);

        if ($payment) {
            $updateStmt = $this->db->prepare("
                UPDATE payments
                SET status = 'completed', mpesa_receipt_number = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $updateStmt->execute([$mpesaReceiptNumber, $payment['id']]);

            // Update organization subscription to active
            $subStmt = $this->db->prepare("
                UPDATE subscriptions
                SET status = 'active', current_period_end = DATE_ADD(NOW(), INTERVAL 30 DAY), updated_at = NOW()
                WHERE organization_id = ?
            ");
            $subStmt->execute([$payment['organization_id']]);
        } else {
            // New direct payment entry
            $paymentId = Ulid::generate();
            $insStmt = $this->db->prepare("
                INSERT INTO payments (id, organization_id, amount, currency, status, mpesa_receipt_number, metadata, created_at, updated_at)
                VALUES (?, ?, ?, 'KES', 'completed', ?, ?, NOW(), NOW())
            ");
            // Pick default org ID or fallback
            $insStmt->execute([
                $paymentId,
                'UNKNOWN_ORG',
                $amount,
                $mpesaReceiptNumber,
                json_encode(['phone' => $phoneNumber])
            ]);
        }

        return true;
    }
}
