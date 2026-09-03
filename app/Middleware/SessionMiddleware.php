<?php

namespace Benchero\Middleware;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Middleware\MiddlewareInterface;

class SessionMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            $lifetime = (int) env('SESSION_LIFETIME', 480) * 60;
            
            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => env('SESSION_SECURE_COOKIE', false),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name('benchero_session');
            
            $savePath = __DIR__ . '/../../storage/sessions';
            if (!is_dir($savePath)) {
                mkdir($savePath, 0755, true);
            }
            session_save_path($savePath);

            session_start();
        }

        return $next($request);
    }
}
