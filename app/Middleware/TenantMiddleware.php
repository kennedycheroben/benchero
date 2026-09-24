<?php

namespace Benchero\Middleware;

use Benchero\Core\CustomDomainContext;
use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Middleware\MiddlewareInterface;
use Benchero\Services\Domain\TenantResolver;
use League\Plates\Engine;

class TenantMiddleware implements MiddlewareInterface
{
    private TenantResolver $tenantResolver;

    public function __construct(?TenantResolver $tenantResolver = null)
    {
        $this->tenantResolver = $tenantResolver ?? new TenantResolver();
    }

    public function handle(Request $request, callable $next): Response
    {
        $host = $request->host();
        $originalPath = $request->originalPath();

        // 1. Authoritative Custom Domain Host Resolution
        $resolution = $this->tenantResolver->resolveFromHost($host);

        if ($resolution['is_custom_domain']) {
            $request->setIsCustomDomain(true);

            // If the custom domain is not active or not recognized
            if (!$resolution['resolved'] || empty($resolution['organization'])) {
                $templates = new Engine(__DIR__ . '/../../views');
                $templates->registerFunction('url', 'url');
                $templates->registerFunction('club_url', 'club_url');
                $html = $templates->render('errors/404', [
                    'title' => 'Custom Domain Not Connected',
                    'message' => "The domain '{$host}' is not currently connected to an active sports club on Benchero."
                ]);
                return new Response($html, 404, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            $org = $resolution['organization'];

            // Subscription / Entitlement Check for Custom Domain
            if (!$resolution['is_visible'] || !$resolution['is_entitled']) {
                $templates = new Engine(__DIR__ . '/../../views');
                $templates->registerFunction('url', 'url');
                $templates->registerFunction('club_url', 'club_url');
                $html = $templates->render('public/locked_profile', [
                    'org' => $org,
                    'title' => $org['name'] . ' — Subscription Expired'
                ]);
                return new Response($html, 402, ['Content-Type' => 'text/html; charset=utf-8']);
            }

            // Inject authoritative tenant context
            $request->setAttribute('tenant', $org);
            $request->setAttribute('custom_domain_org', $org);
            $request->setAttribute('custom_domain_record', $resolution['domain_record']);

            // Register global active custom domain context for URL helpers
            CustomDomainContext::set($org, $resolution['hostname']);

            // 2. Strict Authentication Separation
            // Customer domains must NEVER serve authentication or administrative routes.
            if ($this->isAuthOrAdminPath($originalPath)) {
                $canonicalAppUrl = rtrim(env('APP_URL', 'https://benchero.co.ke'), '/');
                if (!str_starts_with($canonicalAppUrl, 'http://') && !str_starts_with($canonicalAppUrl, 'https://')) {
                    $canonicalAppUrl = 'https://' . $canonicalAppUrl;
                }
                return Response::redirect($canonicalAppUrl . $originalPath, 302);
            }

            // Cross-Tenant Boundary Protection: If someone requests /club/{another_slug}, reject with 404
            if (str_starts_with($originalPath, '/club/')) {
                $parts = explode('/', trim($originalPath, '/'));
                $requestedSlug = $parts[1] ?? '';
                if ($requestedSlug !== '' && $requestedSlug !== $org['slug']) {
                    $templates = new Engine(__DIR__ . '/../../views');
                    $templates->registerFunction('url', 'url');
                    $templates->registerFunction('club_url', 'club_url');
                    $html = $templates->render('errors/404', [
                        'title' => 'Page Not Found',
                        'message' => "The requested club '{$requestedSlug}' is not accessible on this custom domain."
                    ]);
                    return new Response($html, 404, ['Content-Type' => 'text/html; charset=utf-8']);
                }
            }

            // 3. Custom-Domain Public Club Routing
            $this->routeCustomDomainRequest($request, $org['slug'], $originalPath);

            return $next($request);
        }

        // If not a custom domain request, reset custom domain context
        CustomDomainContext::reset();

        // 4. Standard Benchero Domain Path-Based Tenant Resolution (/o/{slug}/*)
        $path = $request->path();
        if (strpos($path, '/o/') === 0) {
            $parts = explode('/', trim($path, '/'));
            if (count($parts) >= 2) {
                $slug = $parts[1];

                $db = \Benchero\Core\Database\Database::getConnection();

                // Find the organization
                $stmt = $db->prepare("SELECT * FROM `organizations` WHERE `slug` = :slug AND `deleted_at` IS NULL");
                $stmt->execute(['slug' => $slug]);
                $organization = $stmt->fetch(\PDO::FETCH_ASSOC);

                if (!$organization) {
                    return new Response('404 Not Found', 404);
                }

                $userId = $_SESSION['_user_id'] ?? null;
                if (!$userId) {
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

    /**
     * Determine if a requested path belongs to authentication or platform administration.
     */
    private function isAuthOrAdminPath(string $path): bool
    {
        $restrictedPrefixes = [
            '/login',
            '/register',
            '/logout',
            '/verify-email',
            '/forgot-password',
            '/reset-password',
            '/admin',
            '/o',
            '/onboarding',
            '/organizations'
        ];

        foreach ($restrictedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/') || str_starts_with($path, $prefix . '?')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Map clean custom domain paths to existing public club controller routes.
     * Enforces strict tenant isolation: any request with /club/{other_slug} is forced to the custom domain's org slug.
     */
    private function routeCustomDomainRequest(Request $request, string $orgSlug, string $path): void
    {
        // Static assets and files are served directly without route mapping
        if (
            str_starts_with($path, '/assets/') ||
            str_starts_with($path, '/uploads/') ||
            str_starts_with($path, '/css/') ||
            str_starts_with($path, '/js/') ||
            str_starts_with($path, '/images/') ||
            $path === '/favicon.ico' ||
            $path === '/favicon.png'
        ) {
            return;
        }

        // Case 1: Root path `/` or empty -> map to `/club/{org_slug}`
        if ($path === '/' || $path === '') {
            $request->setPath('/club/' . $orgSlug);
            return;
        }

        // Case 2: Redundant `/club/{slug}/...` path on custom domain
        if (str_starts_with($path, '/club/')) {
            $parts = explode('/', trim($path, '/'));
            // Force the slug to the verified custom domain's slug (TENANT ISOLATION)
            $parts[1] = $orgSlug;
            $request->setPath('/' . implode('/', $parts));
            return;
        }

        // Case 3: Standard subpaths like `/about`, `/teams`, `/players`, `/fixtures`, `/contact`, etc.
        $request->setPath('/club/' . $orgSlug . '/' . ltrim($path, '/'));
    }
}
