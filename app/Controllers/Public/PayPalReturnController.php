<?php

namespace Benchero\Controllers\Public;

use Benchero\Core\Controller;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;
use Benchero\Services\PaymentService;
use PDO;

class PayPalReturnController extends Controller
{
    private PaymentService $paymentService;
    private PDO $db;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new PaymentService();
        $this->db = Database::getConnection();
    }

    /**
     * Handle browser redirect return from PayPal checkout.
     * CRITICAL: Never trusts the redirect alone. Always executes server-side
     * capture and verification against PayPal before activating subscription.
     */
    public function return(Request $request): Response
    {
        $orderId = trim((string)($request->input('token') ?? $request->input('order_id') ?? ''));

        if (empty($orderId)) {
            $_SESSION['error'] = 'Invalid payment return request.';
            return Response::redirect('/login');
        }

        // Look up corresponding payment intent
        $stmt = $this->db->prepare("
            SELECT pi.*, o.slug as org_slug 
            FROM payment_intents pi
            JOIN organizations o ON pi.organization_id = o.id
            WHERE pi.provider_reference = ? OR pi.reference = ? OR pi.payment_intent_id = ? OR pi.id = ?
            LIMIT 1
        ");
        $stmt->execute([$orderId, $orderId, $orderId, $orderId]);
        $intent = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$intent) {
            $_SESSION['error'] = 'Unable to locate payment reference.';
            return Response::redirect('/login');
        }

        $orgSlug = $intent['org_slug'];
        $billingUrl = "/o/{$orgSlug}/billing";

        // If already completed via AJAX capture or asynchronous webhook, redirect with success
        if ($intent['status'] === 'completed') {
            $_SESSION['success'] = 'Payment verified! Your Benchero subscription is active.';
            return Response::redirect($billingUrl);
        }

        // Execute server-side capture verification
        try {
            $captureResult = $this->paymentService->capturePayPalPayment($intent['id'], $orderId);

            if ($captureResult['success'] ?? false) {
                $_SESSION['success'] = 'Payment verified via PayPal! Your Benchero subscription is now active.';
            } else {
                $_SESSION['error'] = $captureResult['message'] ?? 'PayPal payment could not be confirmed.';
            }
        } catch (\Throwable $e) {
            $_SESSION['error'] = 'An error occurred during payment verification. Please contact support.';
        }

        return Response::redirect($billingUrl);
    }

    /**
     * Handle browser redirect cancellation from PayPal checkout.
     */
    public function cancel(Request $request): Response
    {
        $orderId = trim((string)($request->input('token') ?? $request->input('order_id') ?? ''));

        if (!empty($orderId)) {
            $stmt = $this->db->prepare("
                SELECT pi.*, o.slug as org_slug 
                FROM payment_intents pi
                JOIN organizations o ON pi.organization_id = o.id
                WHERE pi.provider_reference = ? OR pi.reference = ? OR pi.payment_intent_id = ? OR pi.id = ?
                LIMIT 1
            ");
            $stmt->execute([$orderId, $orderId, $orderId, $orderId]);
            $intent = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($intent) {
                if ($intent['status'] === 'pending' || $intent['status'] === 'initiated') {
                    $up = $this->db->prepare("UPDATE payment_intents SET status = 'cancelled', updated_at = NOW() WHERE id = ?");
                    $up->execute([$intent['id']]);
                }

                $_SESSION['info'] = 'PayPal checkout was cancelled. You have not been charged.';
                return Response::redirect("/o/{$intent['org_slug']}/billing");
            }
        }

        $_SESSION['info'] = 'Payment checkout was cancelled.';
        return Response::redirect('/login');
    }
}
