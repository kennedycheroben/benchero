<?php

namespace Benchero\Core\Http;

class Request
{
    private array $get;
    private array $post;
    private array $server;
    private array $cookie;
    private array $files;
    private array $attributes = [];
    private mixed $parsedBody = null;

    public function __construct(array $get, array $post, array $server, array $cookie, array $files)
    {
        $this->get = $get;
        $this->post = $post;
        $this->server = $server;
        $this->cookie = $cookie;
        $this->files = $files;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public static function capture(): self
    {
        return new self($_GET, $_POST, $_SERVER, $_COOKIE, $_FILES);
    }

    public function method(): string
    {
        $method = $this->server['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($this->post['_method'])) {
            $override = strtoupper($this->post['_method']);
            if (in_array($override, ['PUT', 'PATCH', 'DELETE'])) {
                return $override;
            }
        }
        return strtoupper($method);
    }

    public function path(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        
        // Remove base path if applicable (e.g. /benchero or /benchero/public)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName); // e.g. /benchero/public
        
        if ($basePath !== '/' && $basePath !== '\\') {
            // Check if URI starts with /benchero/public
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            } else {
                // If the URI is just /benchero/login (redirected silently to public/)
                // we should strip /benchero
                $parentBase = dirname($basePath); // e.g. /benchero
                if ($parentBase !== '/' && $parentBase !== '\\' && strpos($uri, $parentBase) === 0) {
                    $uri = substr($uri, strlen($parentBase));
                }
            }
        }

        return '/' . ltrim($uri, '/');
    }

    public function query(): array
    {
        return $this->get;
    }

    public function body(): array
    {
        if ($this->parsedBody !== null) {
            return $this->parsedBody;
        }

        if (strpos($this->header('Content-Type'), 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $this->parsedBody = json_decode($input, true) ?? [];
        } else {
            $this->parsedBody = $this->post;
        }

        return $this->parsedBody;
    }

    public function header(string $name, string $default = ''): string
    {
        $name = str_replace('-', '_', strtoupper($name));
        return $this->server['HTTP_' . $name] ?? $this->server[$name] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $body = $this->body();
        return $body[$key] ?? $this->get[$key] ?? $default;
    }

    public function validateCsrf(): bool
    {
        $token = $this->input('_csrf') ?? $this->input('csrf_token') ?? $this->header('X-CSRF-Token');
        return isset($_SESSION['_csrf_token']) && hash_equals($_SESSION['_csrf_token'], (string)$token);
    }
}
