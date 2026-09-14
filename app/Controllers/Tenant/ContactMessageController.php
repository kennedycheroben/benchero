<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Controller;
use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use PDO;

class ContactMessageController extends Controller
{
    private function requireManagerRole(Request $request): void
    {
        $role = $request->getAttribute('tenant_role');
        if (!in_array($role, ['owner', 'admin', 'manager'])) {
            throw new \Exception('403 Forbidden - Insufficient permissions');
        }
    }

    public function index(Request $request): Response
    {
        $tenant = $request->getAttribute('tenant');
        $statusFilter = $request->get('status', 'all');

        $db = Database::getConnection();

        $query = "SELECT * FROM contact_messages WHERE organization_id = ? AND deleted_at IS NULL";
        $params = [$tenant['id']];

        if (in_array($statusFilter, ['unread', 'read', 'archived'])) {
            $query .= " AND status = ?";
            $params[] = $statusFilter;
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute($params);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Fetch count statistics
        $countStmt = $db->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'unread' THEN 1 ELSE 0 END) as unread,
                SUM(CASE WHEN status = 'read' THEN 1 ELSE 0 END) as read_count,
                SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) as archived
            FROM contact_messages
            WHERE organization_id = ? AND deleted_at IS NULL
        ");
        $countStmt->execute([$tenant['id']]);
        $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

        return $this->render('tenant/contact_messages/index', [
            'tenant' => $tenant,
            'messages' => $messages,
            'statusFilter' => $statusFilter,
            'counts' => [
                'total' => (int)($counts['total'] ?? 0),
                'unread' => (int)($counts['unread'] ?? 0),
                'read' => (int)($counts['read_count'] ?? 0),
                'archived' => (int)($counts['archived'] ?? 0)
            ],
            'success' => $request->getFlash('success'),
            'error' => $request->getFlash('error')
        ]);
    }

    public function updateStatus(Request $request, array $params): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $msgId = $params['id'] ?? '';
        $newStatus = $request->post('status', 'read');

        if (!in_array($newStatus, ['unread', 'read', 'archived'])) {
            $newStatus = 'read';
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE contact_messages
            SET status = ?, updated_at = NOW()
            WHERE id = ? AND organization_id = ? AND deleted_at IS NULL
        ");
        $stmt->execute([$newStatus, $msgId, $tenant['id']]);

        $request->setFlash('success', 'Message status updated.');
        return $this->redirect('/o/' . urlencode($tenant['slug']) . '/contact-messages');
    }

    public function delete(Request $request, array $params): Response
    {
        try {
            $this->requireManagerRole($request);
        } catch (\Exception $e) {
            return new Response($e->getMessage(), 403);
        }

        if (!$request->validateCsrf()) {
            return new Response('403 Forbidden - CSRF failed', 403);
        }

        $tenant = $request->getAttribute('tenant');
        $msgId = $params['id'] ?? '';

        $db = Database::getConnection();
        $stmt = $db->prepare("
            UPDATE contact_messages
            SET deleted_at = NOW()
            WHERE id = ? AND organization_id = ?
        ");
        $stmt->execute([$msgId, $tenant['id']]);

        $request->setFlash('success', 'Contact message removed.');
        return $this->redirect('/o/' . urlencode($tenant['slug']) . '/contact-messages');
    }
}
