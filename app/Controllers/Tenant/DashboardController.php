<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Database\Database;

class DashboardController
{
    public function index(Request $request, string $slug): Response
    {
        $tenant = $request->getAttribute('tenant');
        $role = $request->getAttribute('tenant_role');
        
        if (!$tenant) {
            return new Response('404 Not Found', 404);
        }

        $db = Database::getConnection();
        
        // Fetch subscription
        $stmt = $db->prepare("SELECT * FROM `subscriptions` WHERE `organization_id` = :org_id");
        $stmt->execute(['org_id' => $tenant['id']]);
        $subscription = $stmt->fetch(\PDO::FETCH_ASSOC);

        return Response::view('tenant/dashboard', [
            'tenant' => $tenant,
            'role' => $role,
            'subscription' => $subscription
        ]);
    }
}
