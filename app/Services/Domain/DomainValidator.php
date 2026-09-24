<?php

namespace Benchero\Services\Domain;

use InvalidArgumentException;

class DomainValidator
{
    /**
     * Reserved / internal TLDs and names that must not be used as custom domains.
     */
    private const RESERVED_TLDS = [
        'local', 'internal', 'test', 'example', 'invalid', 'localhost', 'lan', 'home', 'corp', 'domain'
    ];

    /**
     * Benchero protected primary domain roots.
     */
    private const PROTECTED_DOMAINS = [
        'benchero.co.ke',
        'benchero.com',
        'benchero.io',
        'benchero.app',
        'teamora.co.ke',
        'teamora.com',
    ];

    /**
     * Normalize and validate a domain or hostname.
     * Returns the normalized domain string.
     *
     * @throws InvalidArgumentException with user-safe error message
     */
    public static function normalizeAndValidate(string $rawInput): string
    {
        $result = self::validate($rawInput);
        if (!$result['valid']) {
            throw new InvalidArgumentException($result['error']);
        }
        return $result['normalized'];
    }

    /**
     * Comprehensive validation returning structured result array.
     *
     * @return array{valid: bool, normalized: ?string, error: ?string}
     */
    public static function validate(string $rawInput): array
    {
        // 1. Initial trim
        $input = trim($rawInput);

        if ($input === '') {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter a domain name.'
            ];
        }

        // 2. Reject internal whitespace
        if (preg_match('/\s/', $input)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Domain name must not contain spaces.'
            ];
        }

        // 3. Reject protocols / URLs
        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $input)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter only the domain name (do not include http:// or https://).'
            ];
        }

        // 4. Reject paths, query strings, and fragments
        if (str_contains($input, '/') || str_contains($input, '\\')) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter only the domain name (do not include URL paths or slashes).'
            ];
        }

        if (str_contains($input, '?') || str_contains($input, '#')) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter only the domain name (do not include query parameters or anchors).'
            ];
        }

        // 5. Reject ports
        if (str_contains($input, ':')) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter only the domain name without a port number.'
            ];
        }

        // 6. Normalize to lowercase and remove single trailing dot
        $normalized = strtolower($input);
        if (str_ends_with($normalized, '.')) {
            $normalized = rtrim($normalized, '.');
        }

        // 7. Check total length (RFC 1035 max 253 characters)
        if (strlen($normalized) > 253) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Domain name exceeds the maximum allowed length of 253 characters.'
            ];
        }

        // 8. Reject raw IP addresses (IPv4 and IPv6)
        if (filter_var($normalized, FILTER_VALIDATE_IP)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'IP addresses cannot be used as custom domains. Please enter a valid registered domain name.'
            ];
        }

        // 9. Reject localhost
        if ($normalized === 'localhost' || str_ends_with($normalized, '.localhost')) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Localhost is an internal hostname and cannot be used as a custom domain.'
            ];
        }

        // 10. Must contain at least one dot separating labels
        $labels = explode('.', $normalized);
        if (count($labels) < 2) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Please enter a complete domain with a top-level domain (e.g. yourclub.co.ke or www.yourclub.com).'
            ];
        }

        // 11. Validate each label according to RFC 1123 / RFC 1035
        foreach ($labels as $index => $label) {
            $len = strlen($label);
            if ($len === 0) {
                return [
                    'valid' => false,
                    'normalized' => null,
                    'error' => 'Domain contains empty labels (consecutive dots are not allowed).'
                ];
            }

            if ($len > 63) {
                return [
                    'valid' => false,
                    'normalized' => null,
                    'error' => "Domain label '{$label}' exceeds the maximum allowed length of 63 characters."
                ];
            }

            // Labels must start and end with an alphanumeric character
            if (!preg_match('/^[a-z0-9]/', $label) || !preg_match('/[a-z0-9]$/', $label)) {
                return [
                    'valid' => false,
                    'normalized' => null,
                    'error' => "Domain label '{$label}' must begin and end with a letter or number."
                ];
            }

            // Label characters can only be alphanumeric or hyphens
            if (!preg_match('/^[a-z0-9-]+$/', $label)) {
                return [
                    'valid' => false,
                    'normalized' => null,
                    'error' => "Domain contains invalid characters in label '{$label}'. Only letters, numbers, and hyphens are permitted."
                ];
            }
        }

        // 12. Validate TLD (last label)
        $tld = end($labels);
        // TLD cannot be purely numeric
        if (preg_match('/^[0-9]+$/', $tld)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Top-level domain cannot be numeric.'
            ];
        }

        if (strlen($tld) < 2) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => 'Top-level domain must be at least 2 characters.'
            ];
        }

        // 13. Reject reserved / internal TLDs
        if (in_array($tld, self::RESERVED_TLDS, true)) {
            return [
                'valid' => false,
                'normalized' => null,
                'error' => "The .{$tld} domain extension is reserved for internal networks and cannot be used."
            ];
        }

        // 14. Reject Benchero-owned infrastructure domains and their subdomains
        foreach (self::PROTECTED_DOMAINS as $protected) {
            if ($normalized === $protected || str_ends_with($normalized, '.' . $protected)) {
                return [
                    'valid' => false,
                    'normalized' => null,
                    'error' => 'Benchero infrastructure domains and their subdomains cannot be registered as customer domains.'
                ];
            }
        }

        // 15. Check configured APP_URL host
        $appUrl = env('APP_URL', '');
        if (!empty($appUrl)) {
            $parsedHost = parse_url($appUrl, PHP_URL_HOST);
            if (!empty($parsedHost)) {
                $parsedHost = strtolower(trim($parsedHost));
                if ($normalized === $parsedHost || str_ends_with($normalized, '.' . $parsedHost)) {
                    return [
                        'valid' => false,
                        'normalized' => null,
                        'error' => 'The primary application domain cannot be registered as a customer custom domain.'
                    ];
                }
            }
        }

        return [
            'valid' => true,
            'normalized' => $normalized,
            'error' => null
        ];
    }
}
