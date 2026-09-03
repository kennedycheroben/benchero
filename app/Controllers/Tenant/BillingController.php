<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\MpesaService;
use PDO;

class BillingController extends Controller
{
    private MpesaService $mpesaService;

    public function __construct()
    {
        parent::__construct();
        $this->mpesaService = new MpesaService();
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $db = Database::getConnection();

        // Fetch organization subscription and plan
        $subStmt = $db->prepare("
            SELECT s.*, p.name as plan_name, p.price_kes, p.features
            FROM subscriptions s
            LEFT JOIN plans p ON s.plan_id = p.id
            WHERE s.organization_id = ?
        ");
        $subStmt->execute([$tenant['id']]);
        $subscription = $subStmt->fetch(PDO::FETCH_ASSOC);

        // Fetch available plans
        $plansStmt = $db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY price_kes ASC");
        $plans = $plansStmt->fetchAll(PDO::FETCH_ASSOC);

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
            $_SESSION['error'] = 'Please enter a valid M-Pesa phone number.';
            return Response::redirect("/o/{$tenant['slug']}/billing");
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM plans WHERE id = ?");
        $stmt->execute([$planId]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            $_SESSION['error'] = 'Invalid plan selected.';
            return Response::redirect("/o/{$tenant['slug']}/billing");
        }

        $res = $this->mpesaService->initiateStkPush($tenant['id'], $phone, (float)$plan['price_kes'], $planId);

        $_SESSION['success'] = 'M-Pesa payment prompt sent to your phone! Complete the PIN prompt to activate your subscription.';
        return Response::redirect("/o/{$tenant['slug']}/billing");
    }
}
