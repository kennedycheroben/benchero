<?php

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string.
     */
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     */
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? false;

        if ($value === false) {
            return $default;
        }

        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $value;
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token from the session.
     */
    function csrf_token(): string
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a CSRF token hidden input field.
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('base_path_url')) {
    /**
     * Determine base path prefix for URLs (e.g. '/teamora' or '/teamora/public' or '').
     */
    function base_path_url(): string
    {
        $envBase = env('BASE_PATH', null);
        if ($envBase !== null) {
            return rtrim($envBase, '/');
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        $publicDir = dirname($scriptName);
        if ($publicDir === '/' || $publicDir === '\\' || $publicDir === '.') {
            $publicDir = '';
        }

        // 1. If REQUEST_URI explicitly starts with /teamora/public
        if ($publicDir !== '' && strpos($requestUri, $publicDir) === 0) {
            return $publicDir;
        }
        
        // 2. If rewrite sent request to teamora/ without /public in REQUEST_URI (e.g. /teamora/login)
        $parentDir = dirname($publicDir);
        if ($parentDir !== '/' && $parentDir !== '\\' && $parentDir !== '.' && strpos($requestUri, $parentDir) === 0) {
            return $parentDir;
        }

        return $publicDir;
    }
}

if (!function_exists('url')) {
    /**
     * Generate a full or relative URL with proper base path.
     */
    function url(string $path = '/'): string
    {
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $base = base_path_url();
        $path = '/' . ltrim($path, '/');
        
        return $base . $path;
    }
}
