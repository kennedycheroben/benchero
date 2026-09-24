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
     * Determine base path prefix for URLs (e.g. '/benchero' or '/benchero/public' or '').
     */
    function base_path_url(): string
    {
        // Custom domains never have a base path prefix
        if (class_exists(\Benchero\Core\CustomDomainContext::class) && \Benchero\Core\CustomDomainContext::isActive()) {
            return '';
        }

        $envBase = env('BASE_PATH', null);
        if ($envBase !== null) {
            return rtrim($envBase, '/');
        }

        if (php_sapi_name() === 'cli') {
            return '';
        }

        $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        
        $publicDir = dirname($scriptName);
        if ($publicDir === '/' || $publicDir === '\\' || $publicDir === '.') {
            $publicDir = '';
        }

        // 1. If REQUEST_URI explicitly starts with /benchero/public
        if ($publicDir !== '' && strpos($requestUri, $publicDir) === 0) {
            return $publicDir;
        }
        
        // 2. If rewrite sent request to benchero/ without /public in REQUEST_URI (e.g. /benchero/login)
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

        // When serving on a verified active custom domain, strip /club/{currentOrgSlug}
        if (\Benchero\Core\CustomDomainContext::isActive()) {
            $activeOrg = \Benchero\Core\CustomDomainContext::getOrg();
            $slug = $activeOrg['slug'] ?? '';
            if ($slug !== '') {
                $prefix = '/club/' . $slug;
                if ($path === $prefix) {
                    $path = '/';
                } elseif (str_starts_with($path, $prefix . '/')) {
                    $path = substr($path, strlen($prefix));
                }
            }
        }

        $base = base_path_url();
        $path = '/' . ltrim($path, '/');
        
        if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) {
            return $path;
        }

        return $base . $path;
    }
}

if (!function_exists('is_test_account')) {
    /**
     * Check if an email, user ID, organization ID, or current logged-in user is a test account.
     * Works in both localhost development and production environments.
     */
    function is_test_account(?string $emailOrId = null): bool
    {
        $testEmailsConfig = env('TEST_ACCOUNT_EMAILS', 'cherobenkennedy34@gmail.com');
        $testEmails = array_map('strtolower', array_map('trim', explode(',', (string)$testEmailsConfig)));
        if (!in_array('cherobenkennedy34@gmail.com', $testEmails, true)) {
            $testEmails[] = 'cherobenkennedy34@gmail.com';
        }

        if (empty($emailOrId)) {
            $emailOrId = $_SESSION['_user_email'] ?? $_SESSION['user_email'] ?? null;
            if (empty($emailOrId) && !empty($_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null)) {
                $emailOrId = $_SESSION['_user_id'] ?? $_SESSION['user_id'];
            }
        }

        if (empty($emailOrId)) {
            return false;
        }

        $target = strtolower(trim((string)$emailOrId));

        if (in_array($target, $testEmails, true)) {
            return true;
        }

        if (strlen($target) === 26) {
            try {
                $db = \Benchero\Core\Database\Database::getConnection();

                // 1. Check if target is a user ID
                $stmt = $db->prepare("SELECT email FROM users WHERE id = ?");
                $stmt->execute([$target]);
                $user = $stmt->fetch(\PDO::FETCH_ASSOC);
                if ($user && in_array(strtolower($user['email']), $testEmails, true)) {
                    return true;
                }

                // 2. Check if target is an organization ID belonging to a test account
                $stmtOrg = $db->prepare("
                    SELECT u.email 
                    FROM users u
                    JOIN organization_user ou ON u.id = ou.user_id
                    WHERE ou.organization_id = ?
                ");
                $stmtOrg->execute([$target]);
                $members = $stmtOrg->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($members as $m) {
                    if (in_array(strtolower($m['email']), $testEmails, true)) {
                        return true;
                    }
                }
            } catch (\Throwable $e) {
                // Ignore DB error
            }
        }

        return false;
    }
}

if (!function_exists('is_custom_domain_request')) {
    function is_custom_domain_request(): bool
    {
        return \Benchero\Core\CustomDomainContext::isActive();
    }
}

if (!function_exists('club_url')) {
    /**
     * Generate URL for public club pages.
     * When accessed via custom domain: generates root-relative path (e.g. /about, /teams).
     * When accessed via primary domain: generates /club/{slug}/{subpath}.
     */
    function club_url(string $subpath = '', ?string $orgSlug = null): string
    {
        $cleanSubpath = ltrim($subpath, '/');

        // If currently serving through a verified active custom domain
        if (\Benchero\Core\CustomDomainContext::isActive()) {
            return url('/' . $cleanSubpath);
        }

        // On primary Benchero domain
        if ($orgSlug === null || $orgSlug === '') {
            $currentOrg = \Benchero\Core\CustomDomainContext::getOrg();
            $orgSlug = $currentOrg['slug'] ?? '';
        }

        $clubPath = '/club/' . $orgSlug . ($cleanSubpath !== '' ? '/' . $cleanSubpath : '');
        return url($clubPath);
    }
}


