<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use Benchero\Services\Cloudflare\CloudflareCustomHostnameService;
use Benchero\Services\Domain\DomainValidator;
use Benchero\Services\Domain\DomainVerificationService;
use Benchero\Services\Domain\TenantResolver;
use InvalidArgumentException;
use PDO;

class DomainService
{
    private PDO $db;
    private EntitlementService $entitlementService;
    private DomainVerificationService $verificationService;
    private TenantResolver $tenantResolver;
    private CloudflareCustomHostnameService $cloudflareService;

    public function __construct(
        ?PDO $db = null,
        ?EntitlementService $entitlementService = null,
        ?DomainVerificationService $verificationService = null,
        ?TenantResolver $tenantResolver = null,
        ?CloudflareCustomHostnameService $cloudflareService = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->entitlementService = $entitlementService ?? new EntitlementService($this->db);
        $this->verificationService = $verificationService ?? new DomainVerificationService();
        $this->tenantResolver = $tenantResolver ?? new TenantResolver($this->db, $this->entitlementService);
        $this->cloudflareService = $cloudflareService ?? new CloudflareCustomHostnameService();
    }

    public function getCloudflareService(): CloudflareCustomHostnameService
    {
        return $this->cloudflareService;
    }

    public function setCloudflareService(CloudflareCustomHostnameService $cloudflareService): void
    {
        $this->cloudflareService = $cloudflareService;
    }


    /**
     * Target Benchero Server CNAME and A Record details for custom domain DNS instructions.
     */
    public static function getDnsInstructions(string $normalizedDomain, string $verificationToken): array
    {
        return DomainVerificationService::getDnsInstructions($normalizedDomain, $verificationToken);
    }

    /**
     * Get domain record for an organization (primary active or latest).
     */
    public function getDomainByOrg(string $orgId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM custom_domains 
            WHERE organization_id = ? AND deleted_at IS NULL 
            ORDER BY is_primary DESC, created_at DESC 
            LIMIT 1
        ");
        $stmt->execute([$orgId]);
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);
        return $domain ?: null;
    }

    /**
     * Get all domain records for an organization (e.g. for replacement flows).
     */
    public function getDomainsByOrg(string $orgId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM custom_domains 
            WHERE organization_id = ? AND deleted_at IS NULL 
            ORDER BY is_primary DESC, created_at DESC
        ");
        $stmt->execute([$orgId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find active organization by custom domain hostname.
     */
    public function getOrgByDomain(string $hostname): ?array
    {
        $resolution = $this->tenantResolver->resolveFromHost($hostname);
        if ($resolution['resolved'] && !empty($resolution['organization'])) {
            return $resolution['organization'];
        }
        return null;
    }

    /**
     * Add or update custom domain for an organization (Benchero Pro feature).
     */
    public function saveDomain(string $orgId, string $rawDomain): array
    {
        // 1. Enforce Pro subscription entitlement
        if (!$this->entitlementService->hasCapability($orgId, EntitlementService::CAP_CUSTOM_DOMAIN)) {
            throw new InvalidArgumentException('Custom domain connections require an active Benchero Pro subscription plan.');
        }

        // 2. Validate and normalize hostname strictly
        $normalizedDomain = DomainValidator::normalizeAndValidate($rawDomain);

        // 3. Check global uniqueness across all organizations on Benchero
        $stmt = $this->db->prepare("
            SELECT organization_id, domain FROM custom_domains 
            WHERE normalized_domain = ? AND organization_id != ? AND deleted_at IS NULL
        ");
        $stmt->execute([$normalizedDomain, $orgId]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException("The domain '{$normalizedDomain}' is already connected to another sports club on Benchero.");
        }

        $existing = $this->getDomainByOrg($orgId);
        $token = DomainVerificationService::generateToken();
        $dnsInstructions = json_encode(self::getDnsInstructions($normalizedDomain, $token));

        if ($existing) {
            // If the domain is identical, re-issue token and reset verification status
            if ($existing['normalized_domain'] === $normalizedDomain) {
                $stmt = $this->db->prepare("
                    UPDATE custom_domains
                    SET domain = ?,
                        normalized_domain = ?,
                        verification_token = ?,
                        verification_status = 'pending',
                        activation_status = 'pending',
                        status = 'pending',
                        ssl_status = 'not_configured',
                        dns_records = ?,
                        verified_at = NULL,
                        activated_at = NULL,
                        last_verification_attempt = NULL,
                        last_verification_error = NULL,
                        updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$normalizedDomain, $normalizedDomain, $token, $dnsInstructions, $existing['id']]);
                $domainId = $existing['id'];
            } else {
                // Staged Replacement: If current domain is active, preserve it while adding the new one as pending
                if ($existing['activation_status'] === 'active') {
                    $domainId = Ulid::generate();
                    $stmt = $this->db->prepare("
                        INSERT INTO custom_domains (
                            id, organization_id, domain, normalized_domain, verification_token,
                            verification_method, verification_status, activation_status,
                            status, ssl_status, dns_records, is_primary, created_at, updated_at
                        ) VALUES (
                            ?, ?, ?, ?, ?,
                            'dns_txt', 'pending', 'pending',
                            'pending', 'not_configured', ?, 0, NOW(), NOW()
                        )
                    ");
                    $stmt->execute([$domainId, $orgId, $normalizedDomain, $normalizedDomain, $token, $dnsInstructions]);
                } else {
                    // Previous domain was not active, update in-place
                    $stmt = $this->db->prepare("
                        UPDATE custom_domains
                        SET domain = ?,
                            normalized_domain = ?,
                            verification_token = ?,
                            verification_status = 'pending',
                            activation_status = 'pending',
                            status = 'pending',
                            ssl_status = 'not_configured',
                            dns_records = ?,
                            verified_at = NULL,
                            activated_at = NULL,
                            last_verification_attempt = NULL,
                            last_verification_error = NULL,
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$normalizedDomain, $normalizedDomain, $token, $dnsInstructions, $existing['id']]);
                    $domainId = $existing['id'];
                }
            }
        } else {
            // New initial domain registration
            $domainId = Ulid::generate();
            $stmt = $this->db->prepare("
                INSERT INTO custom_domains (
                    id, organization_id, domain, normalized_domain, verification_token,
                    verification_method, verification_status, activation_status,
                    status, ssl_status, dns_records, is_primary, created_at, updated_at
                ) VALUES (
                    ?, ?, ?, ?, ?,
                    'dns_txt', 'pending', 'pending',
                    'pending', 'not_configured', ?, 1, NOW(), NOW()
                )
            ");
            $stmt->execute([$domainId, $orgId, $normalizedDomain, $normalizedDomain, $token, $dnsInstructions]);
        }

        $this->logAudit($orgId, 'custom_domain_saved', $domainId, [
            'domain' => $rawDomain,
            'normalized_domain' => $normalizedDomain
        ]);

        $stmtFetch = $this->db->prepare("SELECT * FROM custom_domains WHERE id = ?");
        $stmtFetch->execute([$domainId]);
        return $stmtFetch->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Verify DNS TXT ownership for custom domain.
     */
    public function verifyDomain(string $orgId, ?string $domainId = null): array
    {
        $record = $this->resolveDomainRecord($orgId, $domainId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain found for this organization.');
        }

        $normalizedDomain = $record['normalized_domain'];
        $token = $record['verification_token'];

        // Perform DNS TXT lookup
        $result = $this->verificationService->verifyOwnership($normalizedDomain, $token);

        if ($result['verified']) {
            $cfHostnameId = $record['cloudflare_custom_hostname_id'] ?? null;
            $cfStatus = $record['cloudflare_status'] ?? 'pending';
            $cfSslStatus = $record['cloudflare_ssl_status'] ?? 'pending';
            $cfLastError = null;

            // Trigger Cloudflare Custom Hostname creation if enabled
            if ($this->cloudflareService->isEnabled()) {
                if (empty($cfHostnameId)) {
                    $cfRes = $this->cloudflareService->createCustomHostname($normalizedDomain);
                    if ($cfRes['success']) {
                        $cfHostnameId = $cfRes['id'];
                        $cfStatus = $cfRes['status'] ?? 'pending';
                        $cfSslStatus = $cfRes['ssl_status'] ?? 'pending';
                        $this->logAudit($orgId, 'custom_domain_cf_provisioned', $record['id'], [
                            'domain' => $normalizedDomain,
                            'cf_id' => $cfHostnameId,
                            'status' => $cfStatus,
                            'ssl_status' => $cfSslStatus,
                        ]);
                    } else {
                        $cfLastError = $cfRes['error'] ?? 'Cloudflare provisioning failed';
                        $this->logAudit($orgId, 'custom_domain_cf_provision_failed', $record['id'], [
                            'domain' => $normalizedDomain,
                            'error' => $cfLastError,
                        ]);
                    }
                } else {
                    // Hostname already provisioned in Cloudflare, sync latest status
                    $cfCheck = $this->cloudflareService->getCustomHostnameStatus($cfHostnameId);
                    if ($cfCheck['success']) {
                        $cfStatus = $cfCheck['hostname_status'];
                        $cfSslStatus = $cfCheck['ssl_status'];
                    }
                }
            }

            $stmt = $this->db->prepare("
                UPDATE custom_domains
                SET verification_status = 'verified',
                    status = IF(activation_status = 'active', 'active', 'verified'),
                    verified_at = NOW(),
                    last_verification_attempt = NOW(),
                    last_verification_error = NULL,
                    cloudflare_custom_hostname_id = ?,
                    cloudflare_status = ?,
                    cloudflare_ssl_status = ?,
                    cloudflare_last_checked_at = IF(? IS NOT NULL, NOW(), cloudflare_last_checked_at),
                    cloudflare_last_error = ?,
                    cloudflare_created_at = IF(cloudflare_created_at IS NULL AND ? IS NOT NULL, NOW(), cloudflare_created_at),
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $cfHostnameId,
                $cfStatus,
                $cfSslStatus,
                $cfHostnameId,
                $cfLastError,
                $cfHostnameId,
                $record['id']
            ]);

            $this->logAudit($orgId, 'custom_domain_verified', $record['id'], [
                'domain' => $normalizedDomain
            ]);

            $message = "Ownership for domain {$normalizedDomain} successfully verified!";
            if ($this->cloudflareService->isEnabled()) {
                if (!empty($cfLastError)) {
                    $message .= " Note: Cloudflare provisioning encountered an issue: {$cfLastError}.";
                } elseif ($cfSslStatus === 'active') {
                    $message .= " Cloudflare edge SSL is ready! You can now activate routing.";
                } else {
                    $message .= " Cloudflare edge SSL is currently provisioning. Please ensure your CNAME points to {$this->cloudflareService->getFallbackOrigin()}.";
                }
            } else {
                $message .= " You can now activate routing.";
            }

            return [
                'success' => true,
                'status' => 'verified',
                'cloudflare_status' => $cfStatus,
                'cloudflare_ssl_status' => $cfSslStatus,
                'message' => $message
            ];
        }

        $errorMsg = $result['error'] ?? 'DNS TXT verification check failed. Please ensure records have propagated.';
        $stmt = $this->db->prepare("
            UPDATE custom_domains
            SET verification_status = 'failed',
                status = IF(activation_status = 'active', 'active', 'failed'),
                last_verification_attempt = NOW(),
                last_verification_error = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$errorMsg, $record['id']]);

        $this->logAudit($orgId, 'custom_domain_verification_failed', $record['id'], [
            'domain' => $normalizedDomain,
            'error' => $errorMsg
        ]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => $errorMsg
        ];
    }

    /**
     * Activate a verified custom domain for tenant traffic.
     */
    public function activateDomain(string $orgId, ?string $domainId = null): array
    {
        $record = $this->resolveDomainRecord($orgId, $domainId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain record found to activate.');
        }

        if ($record['verification_status'] !== 'verified') {
            throw new InvalidArgumentException('Domain ownership must be verified before the domain can be activated.');
        }

        if (!$this->entitlementService->hasCapability($orgId, EntitlementService::CAP_CUSTOM_DOMAIN)) {
            throw new InvalidArgumentException('Activating custom domains requires an active Benchero Pro subscription.');
        }

        // Cloudflare prerequisite checks when enabled
        if ($this->cloudflareService->isEnabled()) {
            $cfHostnameId = $record['cloudflare_custom_hostname_id'] ?? null;
            if (empty($cfHostnameId)) {
                $cfRes = $this->cloudflareService->createCustomHostname($record['normalized_domain']);
                if (!$cfRes['success'] || empty($cfRes['id'])) {
                    throw new InvalidArgumentException('Cannot activate domain: Cloudflare custom hostname is not provisioned. ' . ($cfRes['error'] ?? 'Please retry verification.'));
                }
                $cfHostnameId = $cfRes['id'];
                $record['cloudflare_custom_hostname_id'] = $cfHostnameId;
            }

            $cfCheck = $this->cloudflareService->getCustomHostnameStatus($cfHostnameId);
            if ($cfCheck['success']) {
                $cfStatus = $cfCheck['hostname_status'];
                $cfSslStatus = $cfCheck['ssl_status'];

                // Update record with latest Cloudflare state
                $updCf = $this->db->prepare("
                    UPDATE custom_domains 
                    SET cloudflare_status = ?, cloudflare_ssl_status = ?, cloudflare_last_checked_at = NOW(), updated_at = NOW() 
                    WHERE id = ?
                ");
                $updCf->execute([$cfStatus, $cfSslStatus, $record['id']]);

                if (!$cfCheck['is_active']) {
                    $targetCname = $this->cloudflareService->getFallbackOrigin();
                    throw new InvalidArgumentException(
                        "Cannot activate domain yet: Cloudflare edge SSL is currently '{$cfSslStatus}' (hostname: '{$cfStatus}'). " .
                        "Please verify your CNAME record points to '{$targetCname}' and wait for Cloudflare certificate issuance to complete."
                    );
                }
            } else {
                throw new InvalidArgumentException('Failed to check Cloudflare custom hostname status: ' . ($cfCheck['error'] ?? 'network error'));
            }
        }

        // Deactivate any previously active domains for this organization (safe replacement)
        $deactivateStmt = $this->db->prepare("
            UPDATE custom_domains 
            SET activation_status = 'disabled', is_primary = 0, status = 'disabled', updated_at = NOW() 
            WHERE organization_id = ? AND id != ? AND activation_status = 'active'
        ");
        $deactivateStmt->execute([$orgId, $record['id']]);

        // Activate the target domain
        $stmt = $this->db->prepare("
            UPDATE custom_domains
            SET activation_status = 'active',
                is_primary = 1,
                status = 'active',
                activated_at = NOW(),
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$record['id']]);

        $this->logAudit($orgId, 'custom_domain_activated', $record['id'], [
            'domain' => $record['normalized_domain']
        ]);

        $stmtFetch = $this->db->prepare("SELECT * FROM custom_domains WHERE id = ?");
        $stmtFetch->execute([$record['id']]);
        $updatedRecord = $stmtFetch->fetch(PDO::FETCH_ASSOC);

        return array_merge($updatedRecord ?: [], [
            'success' => true,
            'status' => 'active',
            'activation_status' => 'active',
            'message' => "Custom domain {$record['domain']} is now active! Public requests to this domain will now load your club site."
        ]);
    }

    /**
     * Regenerate verification token for a domain.
     */
    public function regenerateToken(string $orgId, ?string $domainId = null): array
    {
        $record = $this->resolveDomainRecord($orgId, $domainId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain found.');
        }

        $token = DomainVerificationService::generateToken();
        $dnsInstructions = json_encode(self::getDnsInstructions($record['normalized_domain'], $token));

        $stmt = $this->db->prepare("
            UPDATE custom_domains
            SET verification_token = ?,
                verification_status = 'pending',
                status = IF(activation_status = 'active', 'active', 'pending'),
                dns_records = ?,
                verified_at = NULL,
                last_verification_attempt = NULL,
                last_verification_error = NULL,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([$token, $dnsInstructions, $record['id']]);

        $this->logAudit($orgId, 'custom_domain_token_regenerated', $record['id'], [
            'domain' => $record['normalized_domain']
        ]);

        return $this->resolveDomainRecord($orgId, $record['id']);
    }

    /**
     * Live TLS/SSL certificate check without faking status.
     * Performs actual TCP connection on port 443 and validates the peer certificate.
     */
    public function checkSslStatus(string $orgId, ?string $domainId = null): array
    {
        $record = $this->resolveDomainRecord($orgId, $domainId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain found.');
        }

        $domain = $record['normalized_domain'];
        $cfHostnameId = $record['cloudflare_custom_hostname_id'] ?? null;

        // If Cloudflare is enabled and hostname ID exists, query Cloudflare API first
        if ($this->cloudflareService->isEnabled() && !empty($cfHostnameId)) {
            $cfCheck = $this->cloudflareService->getCustomHostnameStatus($cfHostnameId);
            if ($cfCheck['success']) {
                $cfStatus = $cfCheck['hostname_status'];
                $cfSslStatus = $cfCheck['ssl_status'];
                $isLive = $cfCheck['is_active'];

                $bencheroSslStatus = CloudflareCustomHostnameService::mapCfSslStatusToBenchero($cfSslStatus);

                $stmt = $this->db->prepare("
                    UPDATE custom_domains 
                    SET cloudflare_status = ?, 
                        cloudflare_ssl_status = ?, 
                        ssl_status = ?,
                        ssl_ready_at = IF(? = 1 AND ssl_ready_at IS NULL, NOW(), ssl_ready_at),
                        cloudflare_last_checked_at = NOW(),
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$cfStatus, $cfSslStatus, $bencheroSslStatus, $isLive ? 1 : 0, $record['id']]);

                if ($isLive) {
                    $this->logAudit($orgId, 'custom_domain_ssl_verified', $record['id'], [
                        'domain' => $domain,
                        'source' => 'cloudflare',
                        'status' => $cfStatus,
                        'ssl_status' => $cfSslStatus,
                    ]);

                    return [
                        'ssl_active' => true,
                        'status' => 'active',
                        'issuer' => 'Cloudflare Edge SSL',
                        'valid_to' => null,
                        'cloudflare_status' => $cfStatus,
                        'cloudflare_ssl_status' => $cfSslStatus,
                        'message' => "Cloudflare edge SSL certificate is active for {$domain}."
                    ];
                }

                return [
                    'ssl_active' => false,
                    'status' => 'pending',
                    'issuer' => null,
                    'valid_to' => null,
                    'cloudflare_status' => $cfStatus,
                    'cloudflare_ssl_status' => $cfSslStatus,
                    'message' => "Cloudflare edge SSL status: '{$cfSslStatus}' (hostname: '{$cfStatus}'). Ensure CNAME points to {$this->cloudflareService->getFallbackOrigin()}."
                ];
            }
        }

        // Secondary / Fallback: Live TCP socket handshake
        $context = stream_context_create([
            'ssl' => [
                'capture_peer_cert' => true,
                'verify_peer' => true,
                'verify_peer_name' => true,
                'SNI_enabled' => true,
                'SNI_server_name' => $domain,
                'allow_self_signed' => false,
            ]
        ]);

        $errno = 0;
        $errstr = '';
        // 5-second connection timeout
        $client = @stream_socket_client(
            "ssl://{$domain}:443",
            $errno,
            $errstr,
            5.0,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if ($client) {
            $params = stream_context_get_params($client);
            $cert = $params['options']['ssl']['peer_certificate'] ?? null;
            fclose($client);

            if ($cert) {
                $parsed = openssl_x509_parse($cert);
                $validTo = $parsed['validTo_time_t'] ?? 0;
                $issuer = $parsed['issuer']['CN'] ?? ($parsed['issuer']['O'] ?? 'Valid CA');

                if ($validTo > time()) {
                    $stmt = $this->db->prepare("
                        UPDATE custom_domains 
                        SET ssl_status = 'active', ssl_ready_at = NOW(), updated_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$record['id']]);

                    $this->logAudit($orgId, 'custom_domain_ssl_verified', $record['id'], [
                        'domain' => $domain,
                        'issuer' => $issuer,
                        'valid_to' => date('Y-m-d H:i:s', $validTo)
                    ]);

                    return [
                        'ssl_active' => true,
                        'status' => 'active',
                        'issuer' => $issuer,
                        'valid_to' => date('Y-m-d', $validTo),
                        'message' => "Valid SSL certificate verified for {$domain} issued by {$issuer}."
                    ];
                }
            }
        }

        // Connection failed or certificate invalid
        $stmt = $this->db->prepare("
            UPDATE custom_domains 
            SET ssl_status = 'pending', updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$record['id']]);

        $failureReason = $errstr !== '' ? $errstr : 'HTTPS port 443 could not be reached or SSL certificate is not yet issued.';

        return [
            'ssl_active' => false,
            'status' => 'pending',
            'issuer' => null,
            'valid_to' => null,
            'message' => "SSL check: {$failureReason}. If your DNS was recently configured, certificate provisioning may take some time."
        ];
    }

    /**
     * Deactivate an active custom domain without deleting its verification records.
     */
    public function deactivateDomain(string $orgId, ?string $domainId = null): array
    {
        $record = $this->resolveDomainRecord($orgId, $domainId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain found to deactivate.');
        }

        $stmt = $this->db->prepare("
            UPDATE custom_domains 
            SET activation_status = 'disabled', status = 'disabled', is_primary = 0, updated_at = NOW() 
            WHERE id = ?
        ");
        $stmt->execute([$record['id']]);

        $this->logAudit($orgId, 'custom_domain_deactivated', $record['id'], [
            'domain' => $record['domain'],
            'normalized_domain' => $record['normalized_domain']
        ]);

        return [
            'success' => true,
            'status' => 'disabled',
            'activation_status' => 'disabled',
            'message' => "Custom domain {$record['domain']} has been deactivated."
        ];
    }

    /**
     * Delete / Disconnect custom domain safely with audit trail.
     */
    public function deleteDomain(string $orgId, ?string $domainId = null): bool
    {
        if ($domainId !== null) {
            $record = $this->resolveDomainRecord($orgId, $domainId);
            if (!$record) {
                return false;
            }

            // Cleanup Cloudflare Custom Hostname if configured
            $cfDeleted = false;
            if ($this->cloudflareService->isEnabled() && !empty($record['cloudflare_custom_hostname_id'])) {
                $cfDelRes = $this->cloudflareService->deleteCustomHostname($record['cloudflare_custom_hostname_id']);
                $cfDeleted = $cfDelRes['success'];
            }

            $this->logAudit($orgId, 'custom_domain_deleted', $record['id'], [
                'domain' => $record['domain'],
                'normalized_domain' => $record['normalized_domain'],
                'cf_hostname_id' => $record['cloudflare_custom_hostname_id'] ?? null,
                'cf_deleted' => $cfDeleted,
            ]);

            $stmt = $this->db->prepare("DELETE FROM custom_domains WHERE id = ? AND organization_id = ?");
            return $stmt->execute([$record['id'], $orgId]);
        }

        $domains = $this->getDomainsByOrg($orgId);
        if (empty($domains)) {
            return false;
        }

        foreach ($domains as $record) {
            $cfDeleted = false;
            if ($this->cloudflareService->isEnabled() && !empty($record['cloudflare_custom_hostname_id'])) {
                $cfDelRes = $this->cloudflareService->deleteCustomHostname($record['cloudflare_custom_hostname_id']);
                $cfDeleted = $cfDelRes['success'];
            }

            $this->logAudit($orgId, 'custom_domain_deleted', $record['id'], [
                'domain' => $record['domain'],
                'normalized_domain' => $record['normalized_domain'],
                'cf_hostname_id' => $record['cloudflare_custom_hostname_id'] ?? null,
                'cf_deleted' => $cfDeleted,
            ]);
        }

        $stmt = $this->db->prepare("DELETE FROM custom_domains WHERE organization_id = ?");
        return $stmt->execute([$orgId]);
    }

    /**
     * Helper to resolve target domain record by ID or default to organization's primary.
     */
    private function resolveDomainRecord(string $orgId, ?string $domainId = null): ?array
    {
        if ($domainId !== null) {
            $stmt = $this->db->prepare("SELECT * FROM custom_domains WHERE id = ? AND organization_id = ? AND deleted_at IS NULL");
            $stmt->execute([$domainId, $orgId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return $this->getDomainByOrg($orgId);
    }

    /**
     * Centralized audit logging for domain lifecycle events.
     */
    private function logAudit(string $orgId, string $action, string $entityId, array $metadata = []): void
    {
        try {
            $userId = $_SESSION['_user_id'] ?? $_SESSION['user_id'] ?? null;
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs (organization_id, user_id, action, entity_type, entity_id, metadata, created_at)
                VALUES (?, ?, ?, 'custom_domain', ?, ?, NOW())
            ");
            $stmt->execute([
                $orgId,
                $userId,
                $action,
                $entityId,
                json_encode($metadata)
            ]);
        } catch (\Throwable $e) {
            // Fail open on audit log errors to prevent interrupting user actions
        }
    }
}
