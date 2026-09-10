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
            
            $isHttps = (
                (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] === 'on' || $_SERVER['HTTPS'] === '1')) ||
                (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ||
                (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            );

            $secureCookie = env('SESSION_SECURE_COOKIE', $isHttps || env('APP_ENV') === 'production');

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => (bool)$secureCookie,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            session_name('benchero_session');
            
            $savePath = __DIR__ . '/../../storage/sessions';
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0755, true);
            }
            session_save_path($savePath);

            session_start();
        }

        return $next($request);
    }
}
