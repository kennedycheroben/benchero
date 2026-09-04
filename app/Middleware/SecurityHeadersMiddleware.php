<?php

namespace Benchero\Middleware;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Middleware\MiddlewareInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $uri = $request->path();
        $isHttps = (
            (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) ||
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
            (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        );

        $host = $_SERVER['HTTP_HOST'] ?? 'benchero.co.ke';
        $isLocalhost = str_contains($host, 'localhost') || str_contains($host, '127.0.0.1');

        // HTTP to HTTPS enforcement in production (excluding local dev environment)
        if (!$isHttps && env('APP_ENV') === 'production' && !$isLocalhost) {
            $targetUrl = 'https://' . $host . $uri;
            return Response::redirect($targetUrl, 301);
        }

        /** @var Response $response */
        $response = $next($request);

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $nonce = base64_encode(random_bytes(16));
        if (!defined('CSP_NONCE')) {
            define('CSP_NONCE', $nonce);
        }

        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "frame-ancestors 'self';";
               
        $response->setHeader('Content-Security-Policy', $csp);

        // HSTS header enabled ONLY when HTTPS is active and APP_HSTS_ENABLED=true
        if ($isHttps && env('APP_HSTS_ENABLED', false)) {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Apply noindex ONLY to private/authenticated routes
        $privatePaths = ['/dashboard', '/account', '/billing', '/admin', '/o/', '/login', '/register', '/reset-password'];
        $isPrivate = false;
        foreach ($privatePaths as $privatePrefix) {
            if (str_starts_with($uri, $privatePrefix)) {
                $isPrivate = true;
                break;
            }
        }

        if ($isPrivate) {
            $response->setHeader('X-Robots-Tag', 'noindex, nofollow');
            $response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        }

        return $response;
    }
}
