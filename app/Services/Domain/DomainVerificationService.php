<?php

namespace Benchero\Services\Domain;

class DomainVerificationService
{
    /**
     * Optional custom DNS resolver callable for testing (e.g. fn(string $host, int $type): array|false).
     */
    private mixed $dnsResolver = null;

    public function __construct(?callable $dnsResolver = null)
    {
        $this->dnsResolver = $dnsResolver;
    }

    /**
     * Generate a cryptographically unpredictable verification token.
     */
    public static function generateToken(): string
    {
        // 48 hex characters from 24 cryptographically secure random bytes
        return bin2hex(random_bytes(24));
    }

    /**
     * Get the authoritative TXT host for DNS ownership verification.
     */
    public static function getVerificationHost(string $normalizedDomain): string
    {
        return '_benchero-verification.' . $normalizedDomain;
    }

    /**
     * Get the expected TXT record value for a given token.
     */
    public static function getExpectedTxtValue(string $token): string
    {
        return 'benchero-verification=' . trim($token);
    }

    /**
     * Get structured DNS instructions for a domain and verification token.
     * Reflects actual configured routing targets.
     */
    public static function getDnsInstructions(string $normalizedDomain, string $token): array
    {
        $verificationHost = self::getVerificationHost($normalizedDomain);
        $expectedTxt = self::getExpectedTxtValue($token);

        // Fetch routing targets from environment / configuration
        $cnameTarget = env('CUSTOM_DOMAIN_CNAME_TARGET', null);
        $aTarget = env('CUSTOM_DOMAIN_A_TARGET', null);

        $step2 = [
            'configured' => !empty($cnameTarget) || !empty($aTarget)
        ];

        if (!empty($cnameTarget)) {
            $step2['cname'] = [
                'type' => 'CNAME',
                'host' => str_starts_with($normalizedDomain, 'www.') ? 'www' : $normalizedDomain,
                'value' => $cnameTarget,
                'ttl' => 3600,
                'description' => 'Points traffic for this hostname to Benchero ingress.'
            ];
        }

        if (!empty($aTarget)) {
            $step2['a_record'] = [
                'type' => 'A',
                'host' => '@',
                'value' => $aTarget,
                'ttl' => 3600,
                'description' => 'Optional root/apex IP routing fallback if your DNS provider does not support CNAME flattening.'
            ];
        }

        return [
            'step_1_ownership' => [
                'type' => 'TXT',
                'host' => '_benchero-verification',
                'fqdn' => $verificationHost,
                'value' => $expectedTxt,
                'ttl' => 300,
                'description' => 'Add this TXT record to prove ownership of your domain.'
            ],
            'step_2_routing' => $step2
        ];
    }

    /**
     * Perform DNS TXT verification check.
     * Returns structured verification result without exposing internal tokens.
     *
     * @return array{verified: bool, error: ?string, records_found: int, attempted_at: string}
     */
    public function verifyOwnership(string $normalizedDomain, string $expectedToken): array
    {
        $attemptedAt = date('Y-m-d H:i:s');
        $verificationHost = self::getVerificationHost($normalizedDomain);
        $expectedValue = self::getExpectedTxtValue($expectedToken);

        $records = $this->queryDnsTxt($verificationHost);

        if ($records === false) {
            return [
                'verified' => false,
                'error' => "DNS lookup query timed out or failed for {$verificationHost}. Please ensure your DNS records have propagated.",
                'records_found' => 0,
                'attempted_at' => $attemptedAt
            ];
        }

        if (empty($records)) {
            return [
                'verified' => false,
                'error' => "No DNS TXT record found at {$verificationHost}. If you just added the record, DNS propagation may take a few minutes.",
                'records_found' => 0,
                'attempted_at' => $attemptedAt
            ];
        }

        $foundValues = [];
        foreach ($records as $record) {
            $txt = '';
            if (isset($record['txt'])) {
                $txt = trim($record['txt']);
            } elseif (isset($record['entries']) && is_array($record['entries'])) {
                $txt = trim(implode('', $record['entries']));
            }

            if ($txt !== '') {
                $foundValues[] = $txt;
                // Check for exact match with expected string
                if (hash_equals($expectedValue, $txt)) {
                    return [
                        'verified' => true,
                        'error' => null,
                        'records_found' => count($records),
                        'attempted_at' => $attemptedAt
                    ];
                }
            }
        }

        // TXT records were returned, but token did not match
        return [
            'verified' => false,
            'error' => "DNS TXT record was found at {$verificationHost}, but the verification token did not match. Please verify the value matches the instructions.",
            'records_found' => count($records),
            'attempted_at' => $attemptedAt
        ];
    }

    /**
     * Alias for verifyOwnership.
     */
    public function verifyDomainOwnership(string $normalizedDomain, string $expectedToken): array
    {
        return $this->verifyOwnership($normalizedDomain, $expectedToken);
    }

    /**
     * Perform DNS TXT lookup safely.
     *
     * @return array|false List of records or false on DNS failure
     */
    private function queryDnsTxt(string $hostname): array|false
    {
        try {
            if ($this->dnsResolver !== null && is_callable($this->dnsResolver)) {
                return call_user_func($this->dnsResolver, $hostname, DNS_TXT);
            }

            // Suppress warnings on network/DNS timeout or failure
            $records = @dns_get_record($hostname, DNS_TXT);
            return is_array($records) ? $records : false;
        } catch (\Throwable $e) {
            return false;
        }
    }
}
