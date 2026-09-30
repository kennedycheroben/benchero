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

    /**
     * Check whether incoming request is from a verified trusted proxy.
     * In production:
     *   - If CLOUDFLARE_WORKER_SECRET is configured, require exact constant-time match.
     *   - If CLOUDFLARE_WORKER_SECRET is empty/missing, DO NOT silently trust X-Forwarded-Host.
     *   - Client-supplied diagnostic headers (like X-Benchero-Proxy-Hop) are NEVER trusted.
     * In development/testing:
     *   - If secret is configured, enforce it.
     *   - If no secret is configured, allow for local dev and automated test suites.
     */
    public function isTrustedProxy(): bool
    {
        $isProduction = (env('APP_ENV') === 'production');
        $workerSecret = (string)env('CLOUDFLARE_WORKER_SECRET', '');

        if ($isProduction) {
            // In production with no worker secret configured: NEVER trust forwarded host
            if ($workerSecret === '') {
                if ((bool)env('CLOUDFLARE_VALIDATE_PROXY_IP', false)) {
                    return $this->isCloudflareIp();
                }
                return false;
            }

            // Secret is configured: require exact constant-time match
            $inboundSecret = $this->header('X-Benchero-Worker-Secret') ?: $this->header('X-CF-Worker-Secret');
            if ($inboundSecret === '' || !hash_equals($workerSecret, $inboundSecret)) {
                return false;
            }

            // Optional defense-in-depth IP validation
            if ((bool)env('CLOUDFLARE_VALIDATE_PROXY_IP', false)) {
                return $this->isCloudflareIp();
            }

            return true;
        }

        // Development / testing environment
        if ($workerSecret !== '') {
            $inboundSecret = $this->header('X-Benchero-Worker-Secret') ?: $this->header('X-CF-Worker-Secret');
            return $inboundSecret !== '' && hash_equals($workerSecret, $inboundSecret);
        }

        if ((bool)env('CLOUDFLARE_VALIDATE_PROXY_IP', false)) {
            return $this->isCloudflareIp();
        }

        // When no secret is enforced in dev/test, allow for local testing
        return true;
    }

    public const CLOUDFLARE_IPV4_RANGES = [
        '173.245.48.0/20',
        '103.21.244.0/22',
        '103.22.200.0/22',
        '103.31.4.0/22',
        '141.101.64.0/18',
        '108.162.192.0/18',
        '190.93.240.0/20',
        '188.114.96.0/20',
        '197.234.240.0/22',
        '198.41.128.0/17',
        '162.158.0.0/15',
        '104.16.0.0/13',
        '104.24.0.0/14',
        '172.64.0.0/13',
        '131.0.72.0/22',
    ];

    public const CLOUDFLARE_IPV6_RANGES = [
        '2400:cb00::/32',
        '2606:4700::/32',
        '2803:f800::/32',
        '2405:b500::/32',
        '2405:8100::/32',
        '2a06:98c0::/29',
        '2c0f:f248::/32',
    ];

    /**
     * Check if an IP address matches an IPv4 or IPv6 CIDR subnet or single IP.
     * Uses inet_pton() binary matching to avoid 32-bit integer overflow and string prefix issues.
     */
    public static function ipMatchesCidr(string $ip, string $cidr): bool
    {
        $ip = trim($ip);
        $cidr = trim($cidr);

        $ipBytes = @inet_pton($ip);
        if ($ipBytes === false) {
            return false;
        }
        $ipLen = strlen($ipBytes);

        $parts = explode('/', $cidr, 2);
        $subnet = trim($parts[0]);
        $prefix = isset($parts[1]) ? (int)trim($parts[1]) : ($ipLen * 8);

        $subnetBytes = @inet_pton($subnet);
        if ($subnetBytes === false || strlen($subnetBytes) !== $ipLen) {
            return false;
        }

        if ($prefix < 0 || $prefix > ($ipLen * 8)) {
            return false;
        }

        $wholeBytes = intdiv($prefix, 8);
        $remainderBits = $prefix % 8;

        if ($wholeBytes > 0 && substr($ipBytes, 0, $wholeBytes) !== substr($subnetBytes, 0, $wholeBytes)) {
            return false;
        }

        if ($remainderBits > 0) {
            $mask = chr((0xFF << (8 - $remainderBits)) & 0xFF);
            if ((ord($ipBytes[$wholeBytes]) & ord($mask)) !== (ord($subnetBytes[$wholeBytes]) & ord($mask))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the configured trusted proxy CIDRs/IPs.
     * Defaults to Cloudflare's published IPv4 and IPv6 ranges.
     * Can be overridden or extended via TRUSTED_PROXY_IPS environment variable.
     */
    public static function getTrustedProxyRanges(): array
    {
        $configured = (string)env('TRUSTED_PROXY_IPS', '');
        if (trim($configured) === '') {
            return array_merge(self::CLOUDFLARE_IPV4_RANGES, self::CLOUDFLARE_IPV6_RANGES);
        }

        $ranges = [];
        $items = array_map('trim', explode(',', $configured));
        foreach ($items as $item) {
            if ($item === '') {
                continue;
            }
            if (strtolower($item) === 'cloudflare' || strtolower($item) === 'default') {
                $ranges = array_merge($ranges, self::CLOUDFLARE_IPV4_RANGES, self::CLOUDFLARE_IPV6_RANGES);
            } else {
                $ranges[] = $item;
            }
        }

        return !empty($ranges) ? $ranges : array_merge(self::CLOUDFLARE_IPV4_RANGES, self::CLOUDFLARE_IPV6_RANGES);
    }

    /**
     * Check if client IP belongs to Cloudflare's published CIDRs (IPv4 and IPv6).
     * If no IP is provided, evaluates the request's REMOTE_ADDR.
     */
    public function isCloudflareIp(?string $ip = null): bool
    {
        $ip = $ip ?? ($this->server['REMOTE_ADDR'] ?? '');
        $ip = trim((string)$ip);
        if ($ip === '') {
            return false;
        }

        foreach (self::CLOUDFLARE_IPV4_RANGES as $range) {
            if (self::ipMatchesCidr($ip, $range)) {
                return true;
            }
        }

        foreach (self::CLOUDFLARE_IPV6_RANGES as $range) {
            if (self::ipMatchesCidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP address belongs to a trusted proxy (Cloudflare or configured in TRUSTED_PROXY_IPS).
     * If no IP is provided, evaluates the request's immediate peer (REMOTE_ADDR).
     */
    public function isTrustedProxyIp(?string $ip = null, ?array $trustedRanges = null): bool
    {
        $ip = $ip ?? ($this->server['REMOTE_ADDR'] ?? '');
        $ip = trim((string)$ip);
        if ($ip === '') {
            return false;
        }

        $ranges = $trustedRanges ?? self::getTrustedProxyRanges();
        foreach ($ranges as $range) {
            if (self::ipMatchesCidr($ip, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the client IP address securely.
     *
     * Trust Model:
     * - Immediate peer is REMOTE_ADDR.
     * - If REMOTE_ADDR is a trusted proxy (Cloudflare proxy CIDRs or configured TRUSTED_PROXY_IPS):
     *     Use validated CF-Connecting-IP header if present and a syntactically valid IPv4/IPv6 address.
     *     If CF-Connecting-IP is missing or invalid, safely fall back to REMOTE_ADDR.
     * - If REMOTE_ADDR is NOT a trusted proxy:
     *     Strictly use REMOTE_ADDR. Ignore CF-Connecting-IP, X-Forwarded-For, X-Real-IP.
     *
     * Never blindly trusts forwarding headers from untrusted direct clients.
     */
    public function clientIp(): string
    {
        $remoteAddr = trim((string)($this->server['REMOTE_ADDR'] ?? ''));

        if ($this->isTrustedProxyIp($remoteAddr)) {
            $cfConnectingIp = trim($this->header('CF-Connecting-IP'));
            if ($cfConnectingIp !== '' && filter_var($cfConnectingIp, FILTER_VALIDATE_IP)) {
                return $cfConnectingIp;
            }
        }

        if ($remoteAddr !== '' && filter_var($remoteAddr, FILTER_VALIDATE_IP)) {
            return $remoteAddr;
        }

        return $remoteAddr !== '' ? $remoteAddr : '0.0.0.0';
    }

    /**
     * Alias for clientIp().
     */
    public function ip(): string
    {
        return $this->clientIp();
    }

    public function host(): string
    {
        if ($this->isTrustedProxy() && !empty($this->server['HTTP_X_FORWARDED_HOST'])) {
            $host = $this->server['HTTP_X_FORWARDED_HOST'];
        } else {
            $host = $this->server['HTTP_HOST'] ?? $this->server['SERVER_NAME'] ?? '';
        }
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
