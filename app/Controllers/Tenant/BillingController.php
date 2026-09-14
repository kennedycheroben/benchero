<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\MpesaService;
use Benchero\Services\SubscriptionService;
use PDO;

class BillingController extends Controller
{
    private MpesaService $mpesaService;
    private SubscriptionService $subscriptionService;

    public function __construct()
    {
        parent::__construct();
        $this->mpesaService = new MpesaService();
        $this->subscriptionService = new SubscriptionService();
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $db = Database::getConnection();

        $subStatus = $this->subscriptionService->getSubscriptionStatus($tenant['id']);
        $subscription = $this->subscriptionService->getSubscription($tenant['id']);
        $plans = $this->subscriptionService->getPlans();

        // Fetch payment history
        $payStmt = $db->prepare("
            SELECT * FROM payments
            WHERE organization_id = ?
            ORDER BY created_at DESC
        ");
        $payStmt->execute([$tenant['id']]);
        $payments = $payStmt->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('tenant/billing/index', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'subStatus' => $subStatus,
            'plans' => $plans,
            'payments' => $payments,
            'error' => $_SESSION['error'] ?? null,
            'success' => $_SESSION['success'] ?? null
        ]);
    }

    public function stkPush(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $phone = trim((string)$request->input('phone_number'));
        $planId = (int)$request->input('plan_id');

        if (empty($phone) || empty($planId)) {
            $_SESSION['error'] = 'Please enter a valid M-Pesa phone number and select a plan.';
            return Response::redirect("/o/{$tenant['slug']}/billing");
        }

        $plan = $this->subscriptionService->getPlan($planId);

        if (!$plan) {
            $_SESSION['error'] = 'Invalid plan selected.';
            return Response::redirect("/o/{$tenant['slug']}/billing");
        }

        $res = $this->mpesaService->initiateStkPush($tenant['id'], $phone, (float)$plan['price_kes'], $planId);

        if (($res['status'] ?? '') === 'completed_mock') {
            $_SESSION['success'] = 'Payment verified! Your Benchero subscription has been activated and your public club profile is now visible.';
        } else {
            $_SESSION['success'] = 'M-Pesa payment prompt sent to ' . htmlspecialchars($phone) . '! Complete the PIN prompt on your phone to activate your subscription.';
        }

        return Response::redirect("/o/{$tenant['slug']}/billing");
    }

    public function createPaymentIntent(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $planId = (int)($request->input('plan_id') ?? $request->post('plan_id'));
        $phone = trim((string)($request->input('phone_number') ?? $request->post('phone_number')));
        $userId = $_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null;

        if (empty($phone) || empty($planId)) {
            return Response::json([
                'success' => false,
                'error' => 'Please provide a valid M-Pesa phone number and plan selection.'
            ], 400);
        }

        $res = $this->mpesaService->createPaymentIntent($tenant['id'], $userId, $planId, $phone);

        if (!($res['success'] ?? false)) {
            return Response::json($res, 400);
        }

        return Response::json($res);
    }

    public function initiatePayment(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $intentId = $request->getAttribute('id') ?? $request->input('id');

        if (empty($intentId)) {
            return Response::json(['success' => false, 'error' => 'Missing payment intent ID.'], 400);
        }

        try {
            $intent = $this->mpesaService->getIntentStatus($intentId);
            if ($intent['organization_id'] !== $tenant['id']) {
                return Response::json(['success' => false, 'error' => 'Unauthorized payment intent access.'], 403);
            }

            $res = $this->mpesaService->initiateStkPush($intent['id'], $intent['phone_number']);
            return Response::json($res);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Failed to initiate payment: ' . $e->getMessage()], 400);
        }
    }

    public function getPaymentStatus(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $intentId = $request->getAttribute('id') ?? $request->input('id');

        if (empty($intentId)) {
            return Response::json(['success' => false, 'error' => 'Missing payment intent ID.'], 400);
        }

        try {
            $intent = $this->mpesaService->getIntentStatus($intentId);
            if ($intent['organization_id'] !== $tenant['id']) {
                return Response::json(['success' => false, 'error' => 'Unauthorized payment intent access.'], 403);
            }

            return Response::json([
                'success' => true,
                'status' => $intent['status'],
                'intent_id' => $intent['id'],
                'payment_intent_id' => $intent['payment_intent_id'] ?? $intent['reference'],
                'reference' => $intent['reference'],
                'amount' => (float)$intent['amount'],
                'mpesa_receipt_number' => $intent['mpesa_receipt_number'] ?? null,
                'result_desc' => $intent['result_desc'] ?? null,
                'created_at' => $intent['created_at'],
                'updated_at' => $intent['updated_at']
            ]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Payment intent not found.'], 440);
        }
    }

    public function testActivatePlan(Request $request): Response
    {
        if (env('APP_ENV') === 'production') {
            return Response::json(['success' => false, 'error' => 'Testing endpoints are disabled in production.'], 403);
        }

        $tenant = $request->getAttribute('tenant');
        $planId = (int)($request->input('plan_id') ?? $request->post('plan_id') ?? 2);

        $activated = $this->subscriptionService->activateSubscription(
            $tenant['id'],
            $planId,
            'DEV_TEST_' . time()
        );

        if ($activated) {
            $_SESSION['success'] = 'Development Test Mode: Plan activated successfully!';
            return Response::json(['success' => true, 'message' => 'Test subscription activated.']);
        }

        return Response::json(['success' => false, 'error' => 'Failed to activate test subscription.'], 400);
    }
}
