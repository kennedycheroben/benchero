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
}
