<?php

namespace Benchero\Core;

use Benchero\Core\Database\Database;
use PDO;

/**
 * Global registry for tenant and sport context during request lifecycle.
 * Ensures consistent tenant, role, and active sport availability across
 * controllers, middleware, view templates, and layout navigation.
 */
class TenantContext
{
    private static ?array $tenant = null;
    private static ?array $sport = null;
    private static ?string $role = null;

    /**
     * Set the current tenant, sport, and role context.
     */
    public static function set(?array $tenant, ?array $sport = null, ?string $role = null): void
    {
        self::$tenant = $tenant;
        self::$sport = $sport;
        self::$role = $role;
    }

    public static function setTenant(?array $tenant): void
    {
        self::$tenant = $tenant;
    }

    public static function setSport(?array $sport): void
    {
        self::$sport = $sport;
    }

    public static function setRole(?string $role): void
    {
        self::$role = $role;
    }

    public static function getTenant(): ?array
    {
        return self::$tenant;
    }

    public static function getSport(): ?array
    {
        return self::$sport;
    }

    public static function getRole(): ?string
    {
        return self::$role;
    }

    public static function hasTenant(): bool
    {
        return self::$tenant !== null && !empty(self::$tenant['id']);
    }

    public static function hasSport(): bool
    {
        return self::$sport !== null && !empty(self::$sport['slug']);
    }

    /**
     * Reset the tenant context (e.g. between requests or non-tenant routes).
     */
    public static function reset(): void
    {
        self::$tenant = null;
        self::$sport = null;
        self::$role = null;
    }

    /**
     * Clear is an alias of reset().
     */
    public static function clear(): void
    {
        self::reset();
    }

    /**
     * Resolve the primary active sport for a given organization ID.
     * Guaranteed to return an active sport belonging to the organization,
     * or null if no active sport exists. Never invents a sport or uses hardcoded IDs.
     */
    public static function resolvePrimarySportForOrg(string $orgId): ?array
    {
        if (empty($orgId)) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.* FROM sports s 
            JOIN organization_sports os ON s.id = os.sport_id 
            WHERE os.organization_id = :org_id 
            AND os.is_active = 1
            ORDER BY s.name ASC 
            LIMIT 1
        ");
        $stmt->execute(['org_id' => $orgId]);
        $sport = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sport ?: null;
    }

    /**
     * Resolve a specific sport by slug for a given organization ID.
     * Guaranteed to return only if the sport belongs to the organization and is active.
     */
    public static function resolveSportBySlugForOrg(string $orgId, string $sportSlug): ?array
    {
        if (empty($orgId) || empty($sportSlug)) {
            return null;
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT s.* FROM sports s 
            JOIN organization_sports os ON s.id = os.sport_id 
            WHERE s.slug = :sport_slug 
            AND os.organization_id = :org_id 
            AND os.is_active = 1
        ");
        $stmt->execute([
            'sport_slug' => $sportSlug,
            'org_id' => $orgId
        ]);
        $sport = $stmt->fetch(PDO::FETCH_ASSOC);

        return $sport ?: null;
    }
}
