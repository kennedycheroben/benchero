<?php

namespace Benchero\Controllers\Admin;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use PDO;

class AdminController extends Controller
{
    private function checkAdminAuth(Request $request): ?array
    {
        $userId = $_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }

        $db = Database::getConnection();
        $userStmt = $db->prepare("
            SELECT u.id, u.name, u.email, u.role, u.is_platform_admin, u.role_id, r.name as role_name, r.display_name as role_display, r.can_view_all_stats
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.id = ? AND u.deleted_at IS NULL
        ");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return null;
        }

        $isAuthorized = in_array($user['role'] ?? '', ['super_admin', 'admin']) ||
                         (int)($user['is_platform_admin'] ?? 0) === 1 || 
                         (int)($user['can_view_all_stats'] ?? 0) === 1 ||
                         in_array($user['role_name'] ?? '', ['super_admin', 'admin']) ||
                         is_test_account($user['email'] ?? null);

        if (!$isAuthorized) {
            return null;
        }

        return $user;
    }

    public function index(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden - Platform Admin Access Required', 403);
        }

        $db = Database::getConnection();

        // Fetch platform statistics
        $orgCount = (int)$db->query("SELECT COUNT(*) FROM organizations WHERE deleted_at IS NULL")->fetchColumn();
        $userCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $fixtureCount = (int)$db->query("SELECT COUNT(*) FROM fixtures WHERE deleted_at IS NULL")->fetchColumn();
        $paymentTotal = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();
        $messagesTotal = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE deleted_at IS NULL")->fetchColumn();
        $unreadMessagesTotal = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread' AND deleted_at IS NULL")->fetchColumn();

        // Fetch system roles & users
        $roles = $db->query("SELECT * FROM roles ORDER BY created_at ASC")->fetchAll(PDO::FETCH_ASSOC);
        $users = $db->query("
            SELECT u.id, u.name, u.email, u.role, u.is_platform_admin, u.role_id, u.created_at,
                   r.name as role_name, r.display_name as role_display
            FROM users u
            LEFT JOIN roles r ON u.role_id = r.id
            WHERE u.deleted_at IS NULL
            ORDER BY u.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        $plans = $db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $recentOrgs = $db->query("SELECT id, name, slug, country, created_at FROM organizations ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $recentPayments = $db->query("SELECT p.*, o.name as org_name FROM payments p LEFT JOIN organizations o ON p.organization_id = o.id ORDER BY p.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $domains = $db->query("SELECT cd.*, o.name as org_name, o.slug as org_slug FROM custom_domains cd LEFT JOIN organizations o ON cd.organization_id = o.id ORDER BY cd.created_at DESC LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);

        $recentMessages = $db->query("
            SELECT cm.*, o.name as org_name
            FROM contact_messages cm
            LEFT JOIN organizations o ON cm.organization_id = o.id
            WHERE cm.deleted_at IS NULL
            ORDER BY cm.created_at DESC
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);

        // Fetch sports platform operational telemetry
        $sportsMatchesCount = (int)($db->query("SELECT COUNT(*) FROM sports_matches")->fetchColumn() ?? 0);
        $sportsNewsCount = (int)($db->query("SELECT COUNT(*) FROM sports_news")->fetchColumn() ?? 0);
        $latestSportsSync = $db->query("SELECT * FROM sports_sync_logs ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

        return $this->render('admin/index', [
            'title' => 'Benchero Platform Administration & Statistics',
            'user' => $user,
            'sportsMatchesCount' => $sportsMatchesCount,
            'sportsNewsCount' => $sportsNewsCount,
            'latestSportsSync' => $latestSportsSync,
            'stats' => [
                'orgs' => $orgCount,
                'users' => $userCount,
                'fixtures' => $fixtureCount,
                'revenue' => $paymentTotal,
                'contact_messages' => $messagesTotal,
                'unread_messages' => $unreadMessagesTotal
            ],
            'roles' => $roles,
            'users' => $users,
            'plans' => $plans,
            'domains' => $domains,
            'recentOrgs' => $recentOrgs,
            'recentPayments' => $recentPayments,
            'recentMessages' => $recentMessages,
            'success' => $request->getFlash('success'),
            'error' => $request->getFlash('error')
        ]);
    }

    public function updateUserRole(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden', 403);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'CSRF validation failed.');
            return $this->redirect('/admin');
        }

        $targetUserId = trim($request->post('user_id', ''));
        $targetRoleId = trim($request->post('role_id', ''));

        if (empty($targetUserId) || empty($targetRoleId)) {
            $request->setFlash('error', 'Please select a valid user and role.');
            return $this->redirect('/admin');
        }

        $db = Database::getConnection();

        // Verify role exists
        $roleStmt = $db->prepare("SELECT id, name, can_view_all_stats FROM roles WHERE id = ?");
        $roleStmt->execute([$targetRoleId]);
        $role = $roleStmt->fetch(PDO::FETCH_ASSOC);

        if (!$role) {
            $request->setFlash('error', 'Selected role does not exist.');
            return $this->redirect('/admin');
        }

        $isPlatformAdmin = in_array($role['name'], ['super_admin', 'admin']) ? 1 : 0;
        $directRole = $role['name'] ?? 'member';

        $updateStmt = $db->prepare("
            UPDATE users
            SET role = ?, role_id = ?, is_platform_admin = ?, updated_at = NOW()
            WHERE id = ? AND deleted_at IS NULL
        ");
        $updateStmt->execute([$directRole, $targetRoleId, $isPlatformAdmin, $targetUserId]);

        $request->setFlash('success', 'User role updated successfully.');
        return $this->redirect('/admin');
    }

    public function updatePlan(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden', 403);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'CSRF validation failed.');
            return $this->redirect('/admin');
        }

        $planId = (int)$request->post('plan_id', 0);
        $priceKes = (float)$request->post('price_kes', 0);
        $name = trim($request->post('name', ''));

        if ($planId <= 0 || empty($name)) {
            $request->setFlash('error', 'Invalid plan update input.');
            return $this->redirect('/admin');
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE plans SET name = ?, price_kes = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$name, $priceKes, $planId]);

        $request->setFlash('success', 'Plan pricing and details updated successfully.');
        return $this->redirect('/admin');
    }

    public function payments(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden - Admin Access Required', 403);
        }

        $db = Database::getConnection();
        $statusFilter = strtolower(trim($request->get('status', 'all')));

        // Stats calculation
        $revenue = (float)$db->query("SELECT COALESCE(SUM(amount), 0) FROM payment_intents WHERE status = 'completed'")->fetchColumn();
        $successfulCount = (int)$db->query("SELECT COUNT(*) FROM payment_intents WHERE status = 'completed'")->fetchColumn();
        $pendingCount = (int)$db->query("SELECT COUNT(*) FROM payment_intents WHERE status IN ('pending', 'initiated')")->fetchColumn();
        $failedCount = (int)$db->query("SELECT COUNT(*) FROM payment_intents WHERE status IN ('failed', 'cancelled', 'expired')")->fetchColumn();

        // Build query
        $where = [];
        $params = [];

        if (in_array($statusFilter, ['completed', 'failed', 'cancelled', 'expired'], true)) {
            $where[] = "pi.status = ?";
            $params[] = $statusFilter;
        } elseif ($statusFilter === 'pending') {
            $where[] = "pi.status IN ('pending', 'initiated')";
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        $sql = "
            SELECT 
                pi.id,
                pi.organization_id,
                pi.user_id,
                pi.plan_id,
                pi.payment_intent_id,
                pi.reference as benchero_reference,
                pi.amount,
                pi.currency,
                pi.base_amount,
                pi.base_currency,
                pi.phone_number,
                pi.payer_email,
                pi.provider,
                pi.payment_method,
                pi.provider_reference,
                pi.status,
                pi.result_code,
                pi.result_desc,
                pi.mpesa_receipt_number,
                pi.created_at,
                o.name as org_name,
                u.name as user_name,
                u.email as user_email,
                p.name as plan_name
            FROM payment_intents pi
            LEFT JOIN organizations o ON pi.organization_id = o.id
            LEFT JOIN users u ON pi.user_id = u.id
            LEFT JOIN plans p ON pi.plan_id = p.id
            {$whereClause}
            ORDER BY pi.created_at DESC
            LIMIT 100
        ";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rawIntents = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $intents = array_map(function ($row) {
            $phone = (string)($row['phone_number'] ?? '');
            if (strlen($phone) >= 9) {
                $maskedPhone = substr($phone, 0, 4) . '****' . substr($phone, -4);
            } elseif (!empty($row['payer_email'])) {
                $parts = explode('@', (string)$row['payer_email']);
                $maskedPhone = substr($parts[0], 0, 2) . '****@' . ($parts[1] ?? '');
            } else {
                $maskedPhone = '—';
            }
            $row['masked_phone'] = $maskedPhone;
            return $row;
        }, $rawIntents);

        return $this->render('admin/payments/index', [
            'title' => 'Benchero Payments & Reconciliation Dashboard',
            'user' => $user,
            'stats' => [
                'revenue' => $revenue,
                'successful' => $successfulCount,
                'pending' => $pendingCount,
                'failed' => $failedCount
            ],
            'currentFilter' => $statusFilter,
            'intents' => $intents,
            'success' => $request->getFlash('success'),
            'error' => $request->getFlash('error')
        ]);
    }

    public function reconcile(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden', 403);
        }

        if (!$request->validateCsrf()) {
            $request->setFlash('error', 'CSRF validation failed.');
            return $this->redirect('/admin/payments');
        }

        $intentRef = trim($request->post('intent_id', ''));
        $receipt = trim($request->post('receipt_number', ''));

        if (empty($intentRef) || empty($receipt)) {
            $request->setFlash('error', 'Please specify a valid Payment Intent reference and Receipt Number.');
            return $this->redirect('/admin/payments');
        }

        $paymentService = new \Benchero\Services\PaymentService();
        $processed = $paymentService->reconcilePayment($intentRef, $receipt, $user['email']);

        if ($processed) {
            $request->setFlash('success', "Payment {$intentRef} reconciled successfully with receipt {$receipt}. Subscription activated.");
        } else {
            $request->setFlash('error', "Could not find matching payment intent or payment is already completed.");
        }

        return $this->redirect('/admin/payments');
    }
}
