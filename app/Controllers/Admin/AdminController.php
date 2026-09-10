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
        $userStmt = $db->prepare("SELECT id, name, email, is_platform_admin FROM users WHERE id = ?");
        $userStmt->execute([$userId]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int)($user['is_platform_admin'] ?? 0) !== 1) {
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
        $orgCount = $db->query("SELECT COUNT(*) FROM organizations WHERE deleted_at IS NULL")->fetchColumn();
        $userCount = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $fixtureCount = $db->query("SELECT COUNT(*) FROM fixtures WHERE deleted_at IS NULL")->fetchColumn();
        $paymentTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

        $plans = $db->query("SELECT * FROM plans WHERE deleted_at IS NULL ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        $recentOrgs = $db->query("SELECT id, name, slug, country, created_at FROM organizations ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $recentPayments = $db->query("SELECT p.*, o.name as org_name FROM payments p LEFT JOIN organizations o ON p.organization_id = o.id ORDER BY p.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $domains = $db->query("SELECT cd.*, o.name as org_name FROM custom_domains cd LEFT JOIN organizations o ON cd.organization_id = o.id ORDER BY cd.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('admin/index', [
            'title' => 'Benchero Platform Administration',
            'user' => $user,
            'stats' => [
                'orgs' => $orgCount,
                'users' => $userCount,
                'fixtures' => $fixtureCount,
                'revenue' => $paymentTotal
            ],
            'plans' => $plans,
            'domains' => $domains,
            'recentOrgs' => $recentOrgs,
            'recentPayments' => $recentPayments,
            'success' => $request->getFlash('success'),
            'error' => $request->getFlash('error')
        ]);
    }

    public function updatePlan(Request $request): Response
    {
        $user = $this->checkAdminAuth($request);
        if (!$user) {
            return new Response('403 Forbidden', 403);
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
}
