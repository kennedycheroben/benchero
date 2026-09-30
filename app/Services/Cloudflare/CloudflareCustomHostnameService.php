<?php

declare(strict_types=1);

namespace Benchero\Services\Cloudflare;

/**
 * CloudflareCustomHostnameService
 * 
 * Manages the Cloudflare for SaaS Custom Hostnames provisioning lifecycle:
 * - Creates Custom Hostnames with edge SSL via Cloudflare API v4
 * - Queries hostname and SSL status
 * - Idempotently handles conflicts (409) and deleted hostnames (404)
 * - Safely handles rate limiting and connection errors
 * - Gated by CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED feature flag
 * - Provides mockable transport for automated unit and integration tests
 */
class CloudflareCustomHostnameService
{
    // Official Cloudflare Custom Hostname Statuses (result.status)
    public const CF_STATUS_PENDING = 'pending';
    public const CF_STATUS_ACTIVE = 'active';
    public const CF_STATUS_PENDING_DELETION = 'pending_deletion';
    public const CF_STATUS_DELETED = 'deleted';
    public const CF_STATUS_BLOCKED = 'blocked';
    public const CF_STATUS_MOVED = 'moved';

    // Official Cloudflare Custom Hostname SSL Statuses (result.ssl.status)
    public const CF_SSL_INITIALIZING = 'initializing';
    public const CF_SSL_PENDING_VALIDATION = 'pending_validation';
    public const CF_SSL_PENDING_ISSUANCE = 'pending_issuance';
    public const CF_SSL_PENDING_DEPLOYMENT = 'pending_deployment';
    public const CF_SSL_ACTIVE = 'active';
    public const CF_SSL_PENDING_DELETION = 'pending_deletion';
    public const CF_SSL_DELETED = 'deleted';
    public const CF_SSL_EXPIRED = 'expired';
    public const CF_SSL_TIMED_OUT = 'timed_out';
    public const CF_SSL_FAILED = 'failed';

    // Benchero Internal SSL Status Enums
    public const BENCHERO_SSL_NOT_CONFIGURED = 'not_configured';
    public const BENCHERO_SSL_PENDING = 'pending';
    public const BENCHERO_SSL_ACTIVE = 'active';
    public const BENCHERO_SSL_FAILED = 'failed';

    private bool $enabled;
    private string $apiToken;
    private string $zoneId;
    private string $fallbackOrigin;
    private string $apiBaseUrl;

    /**
     * Optional custom HTTP transport callable for dependency injection in tests:
     * function(string $method, string $url, array $headers, ?string $body): array{status: int, body: string, headers: array}
     *
     * @var callable|null
     */
    private $transport;

    public function __construct(array $config = [], ?callable $transport = null)
    {
        $this->enabled = (bool)($config['enabled'] ?? (env('CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED', false) === true || env('CLOUDFLARE_CUSTOM_HOSTNAMES_ENABLED', '') === 'true'));
        $this->apiToken = (string)($config['api_token'] ?? env('CLOUDFLARE_API_TOKEN', ''));
        $this->zoneId = (string)($config['zone_id'] ?? env('CLOUDFLARE_ZONE_ID', ''));
        $this->fallbackOrigin = (string)($config['fallback_origin'] ?? env('CLOUDFLARE_FALLBACK_ORIGIN', 'cname.benchero.co.ke'));
        $this->apiBaseUrl = rtrim((string)($config['api_base_url'] ?? env('CLOUDFLARE_API_BASE_URL', 'https://api.cloudflare.com/client/v4')), '/');
        $this->transport = $transport;
    }

    /**
     * Map official Cloudflare SSL status to Benchero internal ssl_status enum.
     */
    public static function mapCfSslStatusToBenchero(?string $cfSslStatus): string
    {
        if ($cfSslStatus === null || $cfSslStatus === '') {
            return self::BENCHERO_SSL_NOT_CONFIGURED;
        }

        $cfSslStatus = strtolower(trim($cfSslStatus));

        switch ($cfSslStatus) {
            case self::CF_SSL_ACTIVE:
                return self::BENCHERO_SSL_ACTIVE;

            case self::CF_SSL_INITIALIZING:
            case self::CF_SSL_PENDING_VALIDATION:
            case self::CF_SSL_PENDING_ISSUANCE:
            case self::CF_SSL_PENDING_DEPLOYMENT:
                return self::BENCHERO_SSL_PENDING;

            case self::CF_SSL_EXPIRED:
            case self::CF_SSL_TIMED_OUT:
            case self::CF_SSL_FAILED:
                return self::BENCHERO_SSL_FAILED;

            case self::CF_SSL_DELETED:
            case self::CF_SSL_PENDING_DELETION:
                return self::BENCHERO_SSL_NOT_CONFIGURED;

            default:
                return self::BENCHERO_SSL_PENDING;
        }
    }

    /**
     * Check if Cloudflare Custom Hostname automation is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get configured fallback origin hostname (e.g. cname.benchero.co.ke).
     */
    public function getFallbackOrigin(): string
    {
        return $this->fallbackOrigin;
    }

    /**
     * Override or inject transport handler (for testing).
     */
    public function setTransport(?callable $transport): void
    {
        $this->transport = $transport;
    }


    /**
     * Create a Custom Hostname in Cloudflare for SaaS.
     *
     * @param string $domain Normalized hostname (e.g. 'sports.cheetahsfc.co.ke')
     * @param array $options Optional custom SSL or origin parameters
     * @return array Standardized result array:
     *               ['success' => bool, 'id' => ?string, 'status' => ?string, 'ssl_status' => ?string, 'error' => ?string, 'bypassed' => bool]
     */
    public function createCustomHostname(string $domain, array $options = []): array
    {
        if (!$this->enabled) {
            return [
                'success' => true,
                'id' => null,
                'status' => 'bypassed',
                'ssl_status' => 'bypassed',
                'error' => null,
                'bypassed' => true,
                'raw' => [],
            ];
        }

        $validation = $this->validateCredentials();
        if (!$validation['success']) {
            return $validation;
        }

        $url = "{$this->apiBaseUrl}/zones/{$this->zoneId}/custom_hostnames";

        $sslMethod = $options['ssl_method'] ?? 'http';
        $payload = [
            'hostname' => $domain,
            'ssl' => [
                'method' => $sslMethod,
                'type' => 'dv',
                'settings' => [
                    'min_tls_version' => $options['min_tls_version'] ?? '1.2',
                    'http2' => 'on',
                ],
            ],
        ];

        // If custom origin server is explicitly requested and permitted
        if (!empty($options['custom_origin_server'])) {
            $payload['custom_origin_server'] = $options['custom_origin_server'];
        }

        $res = $this->request('POST', $url, $payload);

        // 1. Success (200 / 201)
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $data = $res['json']['result'] ?? [];
            return [
                'success' => true,
                'id' => $data['id'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'ssl_status' => $data['ssl']['status'] ?? 'pending',
                'ownership_verification' => $data['ownership_verification'] ?? null,
                'ssl_validation_records' => $data['ssl']['validation_records'] ?? null,
                'error' => null,
                'bypassed' => false,
                'raw' => $data,
            ];
        }

        // 2. Conflict (409) — Domain already exists in Cloudflare for this zone
        // Check for error code 1406 ("A custom hostname for this domain already exists")
        if ($res['status'] === 409 || $this->hasErrorCode($res['json'], 1406)) {
            $existing = $this->getCustomHostnameByDomain($domain);
            if ($existing !== null && !empty($existing['id'])) {
                return [
                    'success' => true,
                    'id' => $existing['id'],
                    'status' => $existing['status'] ?? 'pending',
                    'ssl_status' => $existing['ssl_status'] ?? 'pending',
                    'reused' => true,
                    'error' => null,
                    'bypassed' => false,
                    'raw' => $existing['raw'] ?? [],
                ];
            }
        }

        // 3. API Error
        $errorMsg = $this->extractErrorMessage($res);
        $this->logError("createCustomHostname failed for '{$domain}': {$errorMsg}");

        return [
            'success' => false,
            'id' => null,
            'status' => 'failed',
            'ssl_status' => 'failed',
            'error' => $errorMsg,
            'bypassed' => false,
            'http_status' => $res['status'],
            'raw' => $res['json'] ?? [],
        ];
    }

    /**
     * Retrieve Custom Hostname details by Cloudflare hostname ID.
     */
    public function getCustomHostname(string $hostnameId): array
    {
        if (!$this->enabled) {
            return [
                'success' => true,
                'id' => $hostnameId,
                'status' => 'bypassed',
                'ssl_status' => 'bypassed',
                'error' => null,
                'bypassed' => true,
                'raw' => [],
            ];
        }

        $validation = $this->validateCredentials();
        if (!$validation['success']) {
            return $validation;
        }

        $url = "{$this->apiBaseUrl}/zones/{$this->zoneId}/custom_hostnames/{$hostnameId}";
        $res = $this->request('GET', $url);

        if ($res['status'] >= 200 && $res['status'] < 300) {
            $data = $res['json']['result'] ?? [];
            return [
                'success' => true,
                'id' => $data['id'] ?? $hostnameId,
                'hostname' => $data['hostname'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'ssl_status' => $data['ssl']['status'] ?? 'pending',
                'ownership_verification' => $data['ownership_verification'] ?? null,
                'ssl_validation_records' => $data['ssl']['validation_records'] ?? null,
                'error' => null,
                'bypassed' => false,
                'raw' => $data,
            ];
        }

        $errorMsg = $this->extractErrorMessage($res);
        return [
            'success' => false,
            'id' => $hostnameId,
            'status' => 'error',
            'ssl_status' => 'error',
            'error' => $errorMsg,
            'bypassed' => false,
            'http_status' => $res['status'],
        ];
    }

    /**
     * Find an existing custom hostname in Cloudflare by domain name.
     */
    public function getCustomHostnameByDomain(string $domain): ?array
    {
        $validation = $this->validateCredentials();
        if (!$validation['success']) {
            return null;
        }

        $url = "{$this->apiBaseUrl}/zones/{$this->zoneId}/custom_hostnames?" . http_build_query([
            'hostname' => $domain,
            'per_page' => 1,
        ]);

        $res = $this->request('GET', $url);
        if ($res['status'] >= 200 && $res['status'] < 300) {
            $results = $res['json']['result'] ?? [];
            if (!empty($results[0])) {
                $item = $results[0];
                return [
                    'id' => $item['id'] ?? null,
                    'hostname' => $item['hostname'] ?? $domain,
                    'status' => $item['status'] ?? 'pending',
                    'ssl_status' => $item['ssl']['status'] ?? 'pending',
                    'raw' => $item,
                ];
            }
        }

        return null;
    }

    /**
     * Get consolidated status for a custom hostname (convenience wrapper).
     */
    public function getCustomHostnameStatus(string $hostnameId): array
    {
        $res = $this->getCustomHostname($hostnameId);
        if (!$res['success']) {
            return [
                'success' => false,
                'hostname_status' => 'error',
                'ssl_status' => 'error',
                'is_active' => false,
                'error' => $res['error'] ?? 'Unknown error',
            ];
        }

        if ($res['bypassed'] ?? false) {
            return [
                'success' => true,
                'hostname_status' => 'active',
                'ssl_status' => 'active',
                'is_active' => true,
                'error' => null,
                'bypassed' => true,
            ];
        }

        $hStatus = strtolower((string)($res['status'] ?? 'pending'));
        $sslStatus = strtolower((string)($res['ssl_status'] ?? 'pending'));

        // Cloudflare custom hostname is fully operational when both hostname and SSL are active
        $isActive = ($hStatus === 'active' && $sslStatus === 'active');

        return [
            'success' => true,
            'hostname_status' => $hStatus,
            'ssl_status' => $sslStatus,
            'is_active' => $isActive,
            'error' => null,
            'bypassed' => false,
            'raw' => $res['raw'] ?? [],
        ];
    }

    /**
     * Delete a Custom Hostname from Cloudflare.
     * Idempotent: HTTP 404 (already deleted) is treated as a successful deletion.
     */
    public function deleteCustomHostname(string $hostnameId): array
    {
        if (!$this->enabled) {
            return [
                'success' => true,
                'error' => null,
                'bypassed' => true,
            ];
        }

        if (empty($hostnameId)) {
            return ['success' => true, 'bypassed' => false];
        }

        $validation = $this->validateCredentials();
        if (!$validation['success']) {
            return $validation;
        }

        $url = "{$this->apiBaseUrl}/zones/{$this->zoneId}/custom_hostnames/{$hostnameId}";
        $res = $this->request('DELETE', $url);

        // 200 OK or 404 Not Found (already deleted)
        if (($res['status'] >= 200 && $res['status'] < 300) || $res['status'] === 404) {
            return [
                'success' => true,
                'idempotent' => ($res['status'] === 404),
                'error' => null,
            ];
        }

        $errorMsg = $this->extractErrorMessage($res);
        $this->logError("deleteCustomHostname failed for ID '{$hostnameId}': {$errorMsg}");

        return [
            'success' => false,
            'error' => $errorMsg,
            'http_status' => $res['status'],
        ];
    }

    /**
     * Request re-validation of HTTP DCV for a custom hostname.
     */
    public function triggerSslValidationRetry(string $hostnameId): array
    {
        if (!$this->enabled) {
            return ['success' => true, 'bypassed' => true];
        }

        $validation = $this->validateCredentials();
        if (!$validation['success']) {
            return $validation;
        }

        $url = "{$this->apiBaseUrl}/zones/{$this->zoneId}/custom_hostnames/{$hostnameId}";
        $payload = [
            'ssl' => [
                'method' => 'http',
            ],
        ];

        $res = $this->request('PATCH', $url, $payload);
        return [
            'success' => ($res['status'] >= 200 && $res['status'] < 300),
            'http_status' => $res['status'],
            'error' => ($res['status'] >= 200 && $res['status'] < 300) ? null : $this->extractErrorMessage($res),
        ];
    }

    /**
     * Execute an HTTP request to Cloudflare API with error & retry handling.
     */
    private function request(string $method, string $url, ?array $body = null): array
    {
        $headers = [
            'Authorization: Bearer ' . $this->apiToken,
            'Content-Type: application/json',
            'Accept: application/json',
            'User-Agent: Benchero-Cloudflare-Client/1.0',
        ];

        $jsonBody = $body !== null ? json_encode($body) : null;

        // Custom transport injection (for testing)
        if ($this->transport !== null) {
            try {
                $tRes = ($this->transport)($method, $url, $headers, $jsonBody);
                $parsedJson = json_decode($tRes['body'] ?? '{}', true);
                return [
                    'status' => (int)($tRes['status'] ?? 0),
                    'body' => $tRes['body'] ?? '',
                    'json' => is_array($parsedJson) ? $parsedJson : [],
                ];
            } catch (\Throwable $e) {
                $this->logError("Transport exception on {$method} {$url}: {$e->getMessage()}");
                return [
                    'status' => 0,
                    'body' => '',
                    'json' => ['errors' => [['message' => "Cloudflare API request timed out / connection error: {$e->getMessage()}"]]],
                ];
            }
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

        if ($jsonBody !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        }

        $responseBody = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($responseBody === false) {
            $this->logError("cURL network error on {$method} {$url}: {$curlError}");
            return [
                'status' => 0,
                'body' => '',
                'json' => ['errors' => [['message' => "cURL network error: {$curlError}"]]],
            ];
        }

        $parsedJson = json_decode((string)$responseBody, true);
        return [
            'status' => $httpCode,
            'body' => (string)$responseBody,
            'json' => is_array($parsedJson) ? $parsedJson : [],
        ];
    }

    /**
     * Validate that API credentials are configured.
     */
    private function validateCredentials(): array
    {
        if (empty($this->apiToken)) {
            return [
                'success' => false,
                'error' => 'Cloudflare API token is not configured (CLOUDFLARE_API_TOKEN is empty).',
                'status' => 'unconfigured',
                'ssl_status' => 'unconfigured',
                'bypassed' => false,
            ];
        }

        if (empty($this->zoneId)) {
            return [
                'success' => false,
                'error' => 'Cloudflare Zone ID is not configured (CLOUDFLARE_ZONE_ID is empty).',
                'status' => 'unconfigured',
                'ssl_status' => 'unconfigured',
                'bypassed' => false,
            ];
        }

        return ['success' => true];
    }

    /**
     * Extract human-readable error string from Cloudflare API response.
     */
    private function extractErrorMessage(array $res): string
    {
        $status = $res['status'];
        if ($status === 429) {
            return 'Cloudflare API rate limit exceeded (HTTP 429). Please retry in a few moments.';
        }
        if ($status === 401 || $status === 403) {
            return 'Cloudflare API authentication failed (HTTP ' . $status . '). Check API token permissions.';
        }
        if ($status >= 500) {
            return "Cloudflare API server error (HTTP {$status}). Upstream Cloudflare service temporarily unavailable.";
        }
        if ($status === 0) {
            $msg = $res['json']['errors'][0]['message'] ?? 'Network connection timeout / cURL error';
            return "Cloudflare API connection failed: {$msg}";
        }

        $json = $res['json'] ?? [];
        if (!empty($json['errors']) && is_array($json['errors'])) {
            $msgs = [];
            foreach ($json['errors'] as $err) {
                if (is_array($err) && !empty($err['message'])) {
                    $code = !empty($err['code']) ? " [code {$err['code']}]" : '';
                    $msgs[] = $err['message'] . $code;
                }
            }
            if (!empty($msgs)) {
                return implode('; ', $msgs);
            }
        }

        return "Cloudflare API returned HTTP {$status}";
    }

    /**
     * Check if specific error code exists in JSON errors.
     */
    private function hasErrorCode(array $json, int $code): bool
    {
        if (!empty($json['errors']) && is_array($json['errors'])) {
            foreach ($json['errors'] as $err) {
                if (is_array($err) && ($err['code'] ?? null) === $code) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Safe internal logger without exposing secrets.
     */
    private function logError(string $message): void
    {
        error_log("[CloudflareCustomHostnameService] " . $message);
    }
}
