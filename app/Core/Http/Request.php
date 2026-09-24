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

    private ?string $rewrittenPath = null;
    private bool $isCustomDomain = false;

    public function host(): string
    {
        $host = $this->server['HTTP_X_FORWARDED_HOST'] ?? $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? '';
        return strtolower(trim(explode(':', $host)[0]));
    }

    public function isCustomDomain(): bool
    {
        return $this->isCustomDomain;
    }

    public function setIsCustomDomain(bool $isCustom): void
    {
        $this->isCustomDomain = $isCustom;
    }

    public function setPath(string $path): void
    {
        $this->rewrittenPath = '/' . ltrim($path, '/');
    }

    public function originalPath(): string
    {
        $uri = $this->server['REQUEST_URI'] ?? '/';
        
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);
        
        // Remove base path if applicable (e.g. /benchero or /benchero/public)
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        
        if ($basePath !== '/' && $basePath !== '\\') {
            if (strpos($uri, $basePath) === 0) {
                $uri = substr($uri, strlen($basePath));
            } else {
                $parentBase = dirname($basePath);
                if ($parentBase !== '/' && $parentBase !== '\\' && strpos($uri, $parentBase) === 0) {
                    $uri = substr($uri, strlen($parentBase));
                }
            }
        }

        return '/' . ltrim($uri, '/');
    }

    public function path(): string
    {
        if ($this->rewrittenPath !== null) {
            return $this->rewrittenPath;
        }

        return $this->originalPath();
    }

    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function get(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->get;
        }
        return $this->get[$key] ?? $default;
    }

    public function post(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->post;
        }
        return $this->post[$key] ?? $default;
    }

    public function files(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->files;
        }
        return $this->files[$key] ?? $default;
    }

    public function setFlash(string $key, string $msg): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['_flash'][$key] = $msg;
    }

    public function getFlash(string $key, mixed $default = null): mixed
    {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $val = $_SESSION['_flash'][$key] ?? $default;
        unset($_SESSION['_flash'][$key]);
        return $val;
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
