<?php

namespace Teamora\Middleware;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Core\Middleware\MiddlewareInterface;

class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->setHeader('X-Content-Type-Options', 'nosniff');
        $response->setHeader('X-Frame-Options', 'DENY');
        $response->setHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->setHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        $nonce = base64_encode(random_bytes(16));
        // Store nonce in request for views if needed, or define globally
        if (!defined('CSP_NONCE')) {
            define('CSP_NONCE', $nonce);
        }

        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; " .
               "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; " .
               "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; " .
               "img-src 'self' data: https:; " .
               "connect-src 'self' https://cdn.jsdelivr.net; " .
               "frame-ancestors 'none';";
               
        $response->setHeader('Content-Security-Policy', $csp);

        if (env('APP_HSTS_ENABLED', false) && env('APP_ENV') === 'production') {
            $response->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
