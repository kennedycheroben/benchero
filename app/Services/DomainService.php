<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;
use InvalidArgumentException;
use PDO;

class DomainService
{
    private PDO $db;
    private EntitlementService $entitlementService;

    public function __construct(?PDO $db = null, ?EntitlementService $entitlementService = null)
    {
        $this->db = $db ?? Database::getConnection();
        $this->entitlementService = $entitlementService ?? new EntitlementService($this->db);
    }

    /**
     * Target Benchero Server CNAME and A Record details for custom domain DNS instructions.
     */
    public static function getDnsInstructions(string $domain, string $verificationToken): array
    {
        return [
            'cname_record' => [
                'type' => 'CNAME',
                'host' => 'www',
                'value' => 'cname.benchero.co.ke',
                'ttl' => 3600
            ],
            'a_record' => [
                'type' => 'A',
                'host' => '@',
                'value' => '154.56.40.10', // Server IP
                'ttl' => 3600
            ],
            'txt_record' => [
                'type' => 'TXT',
                'host' => '_benchero-challenge.' . $domain,
                'value' => 'benchero-verification=' . $verificationToken,
                'ttl' => 3600
            ]
        ];
    }

    /**
     * Get domain record for an organization.
     */
    public function getDomainByOrg(string $orgId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM custom_domains WHERE organization_id = ? LIMIT 1");
        $stmt->execute([$orgId]);
        $domain = $stmt->fetch(PDO::FETCH_ASSOC);
        return $domain ?: null;
    }

    /**
     * Find active organization by custom domain hostname.
     */
    public function getOrgByDomain(string $hostname): ?array
    {
        $cleanDomain = strtolower(trim(preg_replace('/^www\./i', '', $hostname)));
        
        $stmt = $this->db->prepare("
            SELECT o.*, cd.domain as custom_domain, cd.status as domain_status
            FROM custom_domains cd
            INNER JOIN organizations o ON cd.organization_id = o.id
            WHERE (cd.domain = ? OR cd.domain = ?) AND cd.status = 'active' AND o.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$cleanDomain, 'www.' . $cleanDomain]);
        $org = $stmt->fetch(PDO::FETCH_ASSOC);
        return $org ?: null;
    }

    /**
     * Add or update custom domain for an organization (Pro feature).
     */
    public function saveDomain(string $orgId, string $rawDomain): array
    {
        if (!$this->entitlementService->hasCapability($orgId, EntitlementService::CAP_CUSTOM_DOMAIN)) {
            throw new InvalidArgumentException('Custom domain connections require a Benchero Pro subscription plan.');
        }

        // Clean & validate domain format
        $domain = strtolower(trim(preg_replace('#^https?://#i', '', $rawDomain)));
        $domain = explode('/', $domain)[0];
        $domain = preg_replace('/^www\./i', '', $domain);

        if (!filter_var('http://' . $domain, FILTER_VALIDATE_URL) || !str_contains($domain, '.')) {
            throw new InvalidArgumentException('Invalid domain format. Example valid format: cheetahsfc.co.ke');
        }

        if (str_ends_with($domain, 'benchero.co.ke') || $domain === 'localhost') {
            throw new InvalidArgumentException('You cannot use the primary Benchero domain as a custom domain.');
        }

        // Check global uniqueness across all tenant domains
        $stmt = $this->db->prepare("SELECT organization_id FROM custom_domains WHERE (domain = ? OR domain = ?) AND organization_id != ?");
        $stmt->execute([$domain, 'www.' . $domain, $orgId]);
        if ($stmt->fetch()) {
            throw new InvalidArgumentException('This domain is already connected to another sports club organization on Benchero.');
        }

        $existing = $this->getDomainByOrg($orgId);
        $token = 'benchero_' . bin2hex(random_bytes(16));
        $dnsInstructions = json_encode(self::getDnsInstructions($domain, $token));

        if ($existing) {
            $stmt = $this->db->prepare("
                UPDATE custom_domains
                SET domain = ?, verification_token = ?, status = 'pending', dns_records = ?, verified_at = NULL, updated_at = NOW()
                WHERE organization_id = ?
            ");
            $stmt->execute([$domain, $token, $dnsInstructions, $orgId]);
            $domainId = $existing['id'];
        } else {
            $domainId = Ulid::generate();
            $stmt = $this->db->prepare("
                INSERT INTO custom_domains (id, organization_id, domain, verification_token, status, dns_records, created_at, updated_at)
                VALUES (?, ?, ?, ?, 'pending', ?, NOW(), NOW())
            ");
            $stmt->execute([$domainId, $orgId, $domain, $token, $dnsInstructions]);
        }

        return $this->getDomainByOrg($orgId);
    }

    /**
     * Verify DNS setup for custom domain.
     */
    public function verifyDomain(string $orgId): array
    {
        $record = $this->getDomainByOrg($orgId);
        if (!$record) {
            throw new InvalidArgumentException('No custom domain found for this organization.');
        }

        $domain = $record['domain'];
        $token = $record['verification_token'];

        // DNS verification logic
        $isVerified = false;
        
        // 1. Check TXT record verification
        $txtHost = '_benchero-challenge.' . $domain;
        $txtRecords = @dns_get_record($txtHost, DNS_TXT);
        if ($txtRecords && is_array($txtRecords)) {
            foreach ($txtRecords as $txt) {
                if (isset($txt['txt']) && str_contains($txt['txt'], $token)) {
                    $isVerified = true;
                    break;
                }
            }
        }

        // 2. Check A / CNAME resolution fallback
        if (!$isVerified) {
            $aRecords = @dns_get_record($domain, DNS_A);
            if ($aRecords && is_array($aRecords)) {
                foreach ($aRecords as $a) {
                    if (isset($a['ip']) && ($a['ip'] === '154.56.40.10' || str_starts_with($a['ip'], '127.'))) {
                        $isVerified = true;
                        break;
                    }
                }
            }
        }

        if ($isVerified) {
            $stmt = $this->db->prepare("
                UPDATE custom_domains
                SET status = 'active', verified_at = NOW(), updated_at = NOW()
                WHERE organization_id = ?
            ");
            $stmt->execute([$orgId]);
            return [
                'success' => true,
                'status' => 'active',
                'message' => "Domain {$domain} successfully verified and activated!"
            ];
        }

        $stmt = $this->db->prepare("UPDATE custom_domains SET status = 'failed', updated_at = NOW() WHERE organization_id = ?");
        $stmt->execute([$orgId]);

        return [
            'success' => false,
            'status' => 'failed',
            'message' => "DNS verification check failed for {$domain}. Please confirm your CNAME or TXT DNS records have propagated."
        ];
    }

    /**
     * Delete custom domain.
     */
    public function deleteDomain(string $orgId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM custom_domains WHERE organization_id = ?");
        return $stmt->execute([$orgId]);
    }
}
