<?php

namespace Teamora\Middleware;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Core\Middleware\MiddlewareInterface;

class TenantMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $path = $request->path();

        // Check if the route is a tenant route
        if (strpos($path, '/o/') === 0) {
            $parts = explode('/', trim($path, '/'));
            if (count($parts) >= 2) {
                $slug = $parts[1];

                $db = \Teamora\Core\Database\Database::getConnection();

                // Find the organization
                $stmt = $db->prepare("SELECT * FROM `organizations` WHERE `slug` = :slug");
                $stmt->execute(['slug' => $slug]);
                $organization = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$organization) {
                    return new Response('404 Not Found', 404);
                }

                $userId = $_SESSION['_user_id'] ?? null;
                if (!$userId) {
                    // Need to be logged in to access tenant pages
                    return Response::redirect('/login');
                }

                // Check membership
                $stmt = $db->prepare("SELECT `role` FROM `organization_user` WHERE `organization_id` = :org_id AND `user_id` = :user_id");
                $stmt->execute([
                    'org_id' => $organization['id'],
                    'user_id' => $userId
                ]);
                $membership = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$membership) {
                    // Logged in but not a member of this tenant
                    return new Response('403 Forbidden - Not a member of this organization', 403);
                }

                // Inject tenant and role into the request
                $request->setAttribute('tenant', $organization);
                $request->setAttribute('tenant_role', $membership['role']);
                
                // Sport resolution
                if (count($parts) >= 4 && $parts[2] === 's') {
                    $sportSlug = $parts[3];
                    
                    $stmt = $db->prepare("
                        SELECT s.* FROM sports s 
                        JOIN organization_sports os ON s.id = os.sport_id 
                        WHERE s.slug = :sport_slug 
                        AND os.organization_id = :org_id 
                        AND os.is_active = 1
                    ");
                    $stmt->execute([
                        'sport_slug' => $sportSlug,
                        'org_id' => $organization['id']
                    ]);
                    $sport = $stmt->fetch(\PDO::FETCH_ASSOC);
                    
                    if (!$sport) {
                        return new Response('404 Not Found - Sport not found or not active for this organization', 404);
                    }
                    
                    $request->setAttribute('sport', $sport);
                }
            }
        }

        return $next($request);
    }
}
