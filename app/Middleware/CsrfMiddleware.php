<?php

namespace Benchero\Middleware;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Middleware\MiddlewareInterface;

class CsrfMiddleware implements MiddlewareInterface
{
    private array $exempt = [
        '/billing/mpesa/callback'
    ];

    public function handle(Request $request, callable $next): Response
    {
        // Generate token if not exists
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        $path = $request->path();
        foreach ($this->exempt as $exemptPath) {
            if (strpos($path, $exemptPath) === 0) {
                return $next($request);
            }
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $token = $request->input('_csrf') ?? $request->input('csrf_token') ?? $request->header('X-CSRF-Token');
            if (!hash_equals($_SESSION['_csrf_token'], (string)$token)) {
                $response = new Response('CSRF token mismatch', 403);
                $response->send();
                exit;
            }
        }

        return $next($request);
    }
}
