<?php

namespace Benchero\Controllers\Tenant;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Services\SubscriptionService;

class DashboardController
{
    public function index(Request $request, string $slug): Response
    {
        $tenant = $request->getAttribute('tenant');
        $role = $request->getAttribute('tenant_role');
        
        if (!$tenant) {
            return new Response('404 Not Found', 404);
        }

        $subService = new SubscriptionService();
        $subscriptionStatus = $subService->getSubscriptionStatus($tenant['id']);

        return Response::view('tenant/dashboard', [
            'tenant' => $tenant,
            'role' => $role,
            'subscription' => $subService->getSubscription($tenant['id']),
            'subscriptionStatus' => $subscriptionStatus
        ]);
    }
}
