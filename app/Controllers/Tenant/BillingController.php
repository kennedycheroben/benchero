<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\PricingConfig;
use Benchero\Services\MpesaService;
use Benchero\Services\PaymentService;
use Benchero\Services\SubscriptionService;
use PDO;

class BillingController extends Controller
{
    private MpesaService $mpesaService;
    private PaymentService $paymentService;
    private SubscriptionService $subscriptionService;

    public function __construct()
    {
        parent::__construct();
        $this->mpesaService = new MpesaService();
        $this->paymentService = new PaymentService();
        $this->subscriptionService = new SubscriptionService();
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant') ?? [
            'id' => $_SESSION['organization_id'] ?? '',
            'name' => 'Organization'
        ];
        $db = Database::getConnection();

        $subStatus = $this->subscriptionService->getSubscriptionStatus($tenant['id']);
        $subscription = $this->subscriptionService->getSubscription($tenant['id']);
        $plans = $this->subscriptionService->getPlans();

        // Attach international pricing to plans
        $plansWithPricing = array_map(function ($plan) {
            $planId = (int)$plan['id'];
            $plan['intl_pricing'] = PricingConfig::getInternationalPrice($planId, 'USD');
            return $plan;
        }, $plans);

        // Fetch payment history with provider and method details
        $payStmt = $db->prepare("
            SELECT * FROM payments
            WHERE organization_id = ?
            ORDER BY created_at DESC
        ");
        $payStmt->execute([$tenant['id']]);
        $payments = $payStmt->fetchAll(\PDO::FETCH_ASSOC);
        $requestedCurrency = strtoupper(trim((string)($request->input('currency') ?? $_SESSION['currency'] ?? $_COOKIE['benchero_currency'] ?? '')));
        if ($requestedCurrency === 'USD') {
            $defaultMethod = 'paypal';
            $_SESSION['currency'] = 'USD';
        } elseif ($requestedCurrency === 'KES') {
            $defaultMethod = 'mpesa';
            $_SESSION['currency'] = 'KES';
        } else {
            $defaultMethod = (strtoupper($tenant['country'] ?? 'KE') === 'KE') ? 'mpesa' : 'paypal';
        }

        // Determine smart pre-selected plan
        $selectedPlanId = null;
        $reqPlan = $request->input('plan');
        if ($reqPlan) {
            if (is_numeric($reqPlan)) {
                $selectedPlanId = (int)$reqPlan;
            } else {
                $slugMap = [
                    'standard-monthly' => 2,
                    'standard-yearly' => 3,
                    'pro-monthly' => 5,
                    'benchero-pro' => 4,
                    'pro-yearly' => 4,
                ];
                $selectedPlanId = $slugMap[strtolower((string)$reqPlan)] ?? null;
            }
        }

        if (!$selectedPlanId && !empty($subStatus['plan_id']) && in_array((int)$subStatus['plan_id'], [2, 3, 4, 5])) {
            $selectedPlanId = (int)$subStatus['plan_id'];
        }

        if (!$selectedPlanId && !empty($payments)) {
            $lastAmount = (float)($payments[0]['amount'] ?? 0);
            if ($lastAmount >= 9000 && $lastAmount <= 11000) {
                $selectedPlanId = 3; // Standard Yearly
            } elseif ($lastAmount >= 900 && $lastAmount <= 1100) {
                $selectedPlanId = 2; // Standard Monthly
            } elseif ($lastAmount >= 19000 && $lastAmount <= 21000) {
                $selectedPlanId = 4; // Pro Yearly
            } elseif ($lastAmount >= 2400 && $lastAmount <= 2600) {
                $selectedPlanId = 5; // Pro Monthly
            }
        }

        if (!$selectedPlanId || !in_array($selectedPlanId, [2, 3, 4, 5])) {
            $selectedPlanId = 5; // Pro Monthly default
        }

        return $this->render('tenant/billing/index', [
            'tenant' => $tenant,
            'subscription' => $subscription,
            'subStatus' => $subStatus,
            'plans' => $plansWithPricing,
            'payments' => $payments,
            'defaultMethod' => $defaultMethod,
            'selectedPlanId' => $selectedPlanId,
            'paypalClientId' => env('PAYPAL_CLIENT_ID', ''),
            'paypalEnvironment' => env('PAYPAL_ENVIRONMENT', 'sandbox'),
            'paypalCurrency' => env('PAYPAL_CURRENCY', 'USD'),
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
        $method = strtolower(trim((string)($request->input('payment_method') ?? $request->post('payment_method') ?? 'mpesa')));
        $phone = trim((string)($request->input('phone_number') ?? $request->post('phone_number') ?? ''));
        $currency = trim((string)($request->input('currency') ?? $request->post('currency') ?? 'USD'));
        $userId = $_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null;

        if (empty($planId)) {
            return Response::json([
                'success' => false,
                'error' => 'Please select a valid subscription plan.'
            ], 400);
        }

        if ($method === 'mpesa' && empty($phone)) {
            return Response::json([
                'success' => false,
                'error' => 'Please provide a valid M-Pesa phone number.'
            ], 400);
        }

        $res = $this->paymentService->createPaymentIntent($tenant['id'], $userId, $planId, $method, [
            'phone_number' => $phone,
            'currency' => $currency
        ]);

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
            $intent = $this->paymentService->getIntentStatus($intentId);
            if ($intent['organization_id'] !== $tenant['id']) {
                return Response::json(['success' => false, 'error' => 'Unauthorized payment intent access.'], 403);
            }

            $res = $this->paymentService->initiatePayment($intent['id'], [
                'phone_number' => $intent['phone_number']
            ]);
            return Response::json($res);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Failed to initiate payment: ' . $e->getMessage()], 400);
        }
    }

    /**
     * Create PayPal Order endpoint for PayPal Checkout popup/smart buttons.
     */
    public function createPayPalOrder(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $planId = (int)($request->input('plan_id') ?? $request->post('plan_id') ?? 4);
        $currency = strtoupper((string)($request->input('currency') ?? $request->post('currency') ?? 'USD'));
        $userId = $_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null;

        $intentRes = $this->paymentService->createPaymentIntent($tenant['id'], $userId, $planId, 'paypal', [
            'currency' => $currency
        ]);

        if (!($intentRes['success'] ?? false)) {
            return Response::json($intentRes, 400);
        }

        $initRes = $this->paymentService->initiatePayment($intentRes['intent_id']);

        if (!($initRes['success'] ?? false)) {
            return Response::json($initRes, 400);
        }

        return Response::json([
            'success' => true,
            'order_id' => $initRes['provider_reference'] ?? $initRes['order_id'],
            'intent_id' => $intentRes['intent_id'],
            'approval_url' => $initRes['approval_url'] ?? null,
            'amount' => $intentRes['amount'],
            'currency' => $intentRes['currency']
        ]);
    }

    /**
     * Capture PayPal Order server-side upon customer approving on PayPal.
     */
    public function capturePayPalOrder(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $orderId = trim((string)($request->input('order_id') ?? $request->post('order_id') ?? ''));
        $intentId = trim((string)($request->input('intent_id') ?? $request->post('intent_id') ?? ''));

        if (empty($orderId) || empty($intentId)) {
            return Response::json([
                'success' => false,
                'error' => 'Missing PayPal order or payment intent ID.'
            ], 400);
        }

        try {
            $intent = $this->paymentService->getIntentStatus($intentId);
            if ($intent['organization_id'] !== $tenant['id']) {
                return Response::json(['success' => false, 'error' => 'Unauthorized payment capture request.'], 403);
            }

            $res = $this->paymentService->capturePayPalPayment($intent['id'], $orderId);

            if ($res['success'] ?? false) {
                $_SESSION['success'] = 'Payment verified via PayPal! Your Benchero subscription is now active.';
            }

            return Response::json($res);
        } catch (\Throwable $e) {
            return Response::json([
                'success' => false,
                'error' => 'Server error capturing payment: ' . $e->getMessage()
            ], 500);
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
            $intent = $this->paymentService->getIntentStatus($intentId);
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
                'currency' => $intent['currency'] ?? 'KES',
                'provider' => $intent['provider'] ?? 'imbank',
                'payment_method' => $intent['payment_method'] ?? 'mpesa',
                'mpesa_receipt_number' => $intent['mpesa_receipt_number'] ?? null,
                'result_desc' => $intent['result_desc'] ?? null,
                'created_at' => $intent['created_at'],
                'updated_at' => $intent['updated_at']
            ]);
        } catch (\Throwable $e) {
            return Response::json(['success' => false, 'error' => 'Payment intent not found.'], 404);
        }
    }

    public function testActivatePlan(Request $request): Response
    {
        if (env('APP_ENV') === 'production') {
            return Response::json(['success' => false, 'error' => 'Testing endpoints are disabled in production.'], 403);
        }

        $tenant = $request->getAttribute('tenant');
        $planId = (int)($request->input('plan_id') ?? $request->post('plan_id') ?? 4);

        $activated = $this->subscriptionService->activateSubscription(
            $tenant['id'],
            $planId,
            'DEV_TEST_' . time(),
            null,
            'imbank'
        );

        if ($activated) {
            $_SESSION['success'] = 'Development Test Mode: Plan activated successfully!';
            return Response::json(['success' => true, 'message' => 'Test subscription activated.']);
        }

        return Response::json(['success' => false, 'error' => 'Failed to activate test subscription.'], 400);
    }
}
