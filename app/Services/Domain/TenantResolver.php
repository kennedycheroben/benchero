<?php

namespace Benchero\Services\Domain;

use Benchero\Core\Database\Database;
use Benchero\Services\EntitlementService;
use Benchero\Services\SubscriptionService;
use PDO;

class TenantResolver
{
    private PDO $db;
    private EntitlementService $entitlementService;
    private SubscriptionService $subscriptionService;

    public function __construct(
        ?PDO $db = null,
        ?EntitlementService $entitlementService = null,
        ?SubscriptionService $subscriptionService = null
    ) {
        $this->db = $db ?? Database::getConnection();
        $this->entitlementService = $entitlementService ?? new EntitlementService($this->db);
        $this->subscriptionService = $subscriptionService ?? new SubscriptionService();
    }

    /**
     * Check if a given normalized hostname is a primary Benchero infrastructure domain.
     */
    public static function isPrimaryInfrastructureHost(string $normalizedHost): bool
    {
        if ($normalizedHost === '' || $normalizedHost === 'localhost' || str_ends_with($normalizedHost, '.localhost')) {
            return true;
        }

        if (str_starts_with($normalizedHost, '127.') || $normalizedHost === '::1') {
            return true;
        }

        $protectedRoots = [
            'benchero.co.ke',
            'benchero.com',
            'benchero.io',
            'benchero.app',
            'teamora.co.ke',
            'teamora.com',
        ];

        foreach ($protectedRoots as $root) {
            if ($normalizedHost === $root || str_ends_with($normalizedHost, '.' . $root)) {
                return true;
            }
        }

        $appUrl = env('APP_URL', '');
        if (!empty($appUrl)) {
            $parsed = parse_url($appUrl, PHP_URL_HOST);
            if (!empty($parsed) && (strtolower(trim($parsed)) === $normalizedHost || str_ends_with($normalizedHost, '.' . strtolower(trim($parsed))))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve organization authoritatively from request Host header.
     *
     * @return array{
     *     is_custom_domain: bool,
     *     hostname: string,
     *     resolved: bool,
     *     organization: ?array,
     *     domain_record: ?array,
     *     is_entitled: bool,
     *     is_visible: bool,
     *     error: ?string
     * }
     */
    public function resolveFromHost(string $rawHost): array
    {
        // 1. Strip port and normalize hostname
        $host = strtolower(trim(explode(':', $rawHost)[0]));
        $host = rtrim($host, '.');

        // 2. If it's a primary Benchero host, it is not a customer custom domain
        if (self::isPrimaryInfrastructureHost($host)) {
            return [
                'is_custom_domain' => false,
                'hostname' => $host,
                'resolved' => false,
                'organization' => null,
                'domain_record' => null,
                'is_entitled' => true,
                'is_visible' => true,
                'error' => null
            ];
        }

        // 3. Query custom_domains for active, verified domain record matching normalized hostname
        $stmt = $this->db->prepare("
            SELECT cd.*, 
                   o.id as org_id, o.name as org_name, o.slug as org_slug, o.country as org_country,
                   o.timezone as org_timezone, o.logo_url as org_logo_url, o.description as org_description,
                   o.club_colors as org_club_colors, o.social_links as org_social_links,
                   o.deleted_at as org_deleted_at
            FROM custom_domains cd
            INNER JOIN organizations o ON cd.organization_id = o.id
            WHERE cd.normalized_domain = ?
              AND cd.activation_status = 'active'
              AND cd.deleted_at IS NULL
              AND o.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$host]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            return [
                'is_custom_domain' => true,
                'hostname' => $host,
                'resolved' => false,
                'organization' => null,
                'domain_record' => null,
                'is_entitled' => false,
                'is_visible' => false,
                'error' => "The domain '{$host}' is not connected to an active organization on Benchero."
            ];
        }

        // 4. Construct organization array
        $org = [
            'id' => $row['org_id'],
            'name' => $row['org_name'],
            'slug' => $row['org_slug'],
            'country' => $row['org_country'],
            'timezone' => $row['org_timezone'],
            'logo_url' => $row['org_logo_url'],
            'description' => $row['org_description'],
            'club_colors' => $row['org_club_colors'],
            'social_links' => $row['org_social_links'],
            'custom_domain' => $row['domain'],
            'domain_id' => $row['id']
        ];

        // 5. Subscription & Entitlement check
        $subStatus = $this->subscriptionService->getSubscriptionStatus($org['id']);
        $isVisible = $this->subscriptionService->isPublicProfileVisible($org['id']);
        $isEntitled = $this->entitlementService->hasCapability($org['id'], EntitlementService::CAP_CUSTOM_DOMAIN);

        return [
            'is_custom_domain' => true,
            'hostname' => $host,
            'resolved' => true,
            'organization' => $org,
            'domain_record' => $row,
            'is_entitled' => $isEntitled,
            'is_visible' => $isVisible,
            'error' => null
        ];
    }
}
