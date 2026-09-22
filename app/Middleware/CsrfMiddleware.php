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
            $sessionToken = $_SESSION['_csrf_token'] ?? '';
            if (!hash_equals($sessionToken, (string)$token)) {
                $accept = $request->header('Accept') ?? '';
                $isAjax = $request->header('X-Requested-With') === 'XMLHttpRequest' || str_contains($accept, 'application/json');

                if ($isAjax) {
                    $response = new Response(json_encode(['error' => 'CSRF token mismatch. Please reload.']), 403, ['Content-Type' => 'application/json']);
                    $response->send();
                    exit;
                }

                $referer = $request->header('Referer') ?? '';
                if ($path === '/login' || str_contains($referer, '/login')) {
                    $_SESSION['error'] = 'Your session expired. Please enter your credentials to log in.';
                    session_write_close();
                    $response = Response::redirect(url('/login'));
                    $response->send();
                    exit;
                }

                if ($path === '/register' || str_contains($referer, '/register')) {
                    $_SESSION['error'] = 'Your session expired. Please submit the form again.';
                    session_write_close();
                    $response = Response::redirect(url('/register'));
                    $response->send();
                    exit;
                }

                $response = new Response('CSRF token mismatch. Please refresh the page and try again.', 403);
                $response->send();
                exit;
            }
        }

        return $next($request);
    }
}
