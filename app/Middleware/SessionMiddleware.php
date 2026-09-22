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

            $secureCookie = env('SESSION_SECURE_COOKIE', false);
            if (!$isHttps) {
                $secureCookie = false;
            }

            session_set_cookie_params([
                'lifetime' => $lifetime,
                'path' => '/',
                'domain' => '',
                'secure' => (bool)$secureCookie,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);

            $sessionName = 'benchero_session';
            session_name($sessionName);
            
            $savePath = __DIR__ . '/../../storage/sessions';
            if (!is_dir($savePath)) {
                @mkdir($savePath, 0777, true);
                @chmod($savePath, 0777);
            } elseif (!is_writable($savePath)) {
                @chmod($savePath, 0777);
            }
            session_save_path($savePath);

            // Clean up any session file if owned by another UID (e.g. CLI runner vs Apache daemon)
            if (!empty($_COOKIE[$sessionName])) {
                $cookieSessId = preg_replace('/[^a-zA-Z0-9,-]/', '', (string)$_COOKIE[$sessionName]);
                if ($cookieSessId !== '') {
                    $sessFile = $savePath . '/sess_' . $cookieSessId;
                    if (file_exists($sessFile) && function_exists('posix_getuid')) {
                        $processUid = posix_getuid();
                        $fileUid = @fileowner($sessFile);
                        if ($fileUid !== false && $fileUid !== $processUid) {
                            @unlink($sessFile);
                        }
                    }
                }
            }

            @session_start();

            if (session_status() === PHP_SESSION_NONE) {
                session_id(bin2hex(random_bytes(16)));
                @session_start();
            }
        }

        return $next($request);
    }
}
