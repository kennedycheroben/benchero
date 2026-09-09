<?php

namespace Benchero\Core;

use League\Plates\Engine;
use Benchero\Core\Http\Response;

abstract class Controller
{
    protected Engine $templates;

    public function __construct()
    {
        $this->templates = new Engine(__DIR__ . '/../../views');
        $this->templates->registerFunction('url', 'url');
    }

    protected function render(string $template, array $data = [], int $statusCode = 200): Response
    {
        $content = $this->templates->render($template, $data);
        return new Response($content, $statusCode, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    protected function json(array $data, int $statusCode = 200): Response
    {
        return Response::json($data, $statusCode);
    }

    protected function redirect(string $url, int $statusCode = 302): Response
    {
        return Response::redirect($url, $statusCode);
    }

    protected function error(string $message = 'Error', int $statusCode = 400, array $data = []): Response
    {
        if (!empty($data) || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            return Response::json(array_merge(['error' => $message], $data), $statusCode);
        }

        $viewName = match ($statusCode) {
            403 => 'errors/403',
            404 => 'errors/404',
            419 => 'errors/419',
            500 => 'errors/500',
            default => null
        };

        if ($viewName) {
            try {
                return $this->render($viewName, array_merge(['error' => $message, 'message' => $message], $data), $statusCode);
            } catch (\Throwable $e) {
                // Fallback if view rendering fails
            }
        }

        return new Response(
            "<div style='font-family: system-ui, sans-serif; padding: 3rem; text-align: center; max-width: 600px; margin: 4rem auto;'>" .
            "<h1 style='font-size: 3rem; margin-bottom: 1rem; color: #0f172a;'>{$statusCode}</h1>" .
            "<p style='font-size: 1.25rem; color: #4b5563;'>" . htmlspecialchars($message) . "</p>" .
            "<a href='/' style='display: inline-block; margin-top: 1.5rem; padding: 0.75rem 1.5rem; background: #2563eb; color: white; border-radius: 0.5rem; text-decoration: none; font-weight: 600;'>Return Home</a>" .
            "</div>",
            $statusCode,
            ['Content-Type' => 'text/html; charset=utf-8']
        );
    }
}
