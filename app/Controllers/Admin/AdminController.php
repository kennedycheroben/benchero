<?php

namespace Benchero\Controllers\Admin;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use PDO;

class AdminController extends Controller
{
    public function index(Request $request): Response
    {
        if (!isset($_SESSION['user_id'])) {
            return Response::redirect('/login');
        }

        $db = Database::getConnection();
        $userStmt = $db->prepare("SELECT id, name, email, is_platform_admin FROM users WHERE id = ?");
        $userStmt->execute([$_SESSION['user_id']]);
        $user = $userStmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || (int)($user['is_platform_admin'] ?? 0) !== 1) {
            return new Response('403 Forbidden - Platform Admin Access Required', 403);
        }

        // Fetch platform statistics
        $orgCount = $db->query("SELECT COUNT(*) FROM organizations WHERE deleted_at IS NULL")->fetchColumn();
        $userCount = $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn();
        $fixtureCount = $db->query("SELECT COUNT(*) FROM fixtures WHERE deleted_at IS NULL")->fetchColumn();
        $paymentTotal = $db->query("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed'")->fetchColumn();

        $recentOrgs = $db->query("SELECT id, name, slug, country, created_at FROM organizations ORDER BY created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
        $recentPayments = $db->query("SELECT p.*, o.name as org_name FROM payments p LEFT JOIN organizations o ON p.organization_id = o.id ORDER BY p.created_at DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

        return $this->render('admin/index', [
            'title' => 'Benchero Platform Administration',
            'user' => $user,
            'stats' => [
                'orgs' => $orgCount,
                'users' => $userCount,
                'fixtures' => $fixtureCount,
                'revenue' => $paymentTotal
            ],
            'recentOrgs' => $recentOrgs,
            'recentPayments' => $recentPayments
        ]);
    }
}
