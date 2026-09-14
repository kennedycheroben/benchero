<?php

namespace Benchero\Services;

use Benchero\Core\Database\Database;
use PDO;

class ContactMessageService
{
    private static array $cache = [];

    /**
     * Get unread contact messages count for a specific organization.
     * Uses in-memory request-level caching.
     */
    public static function getUnreadCount(string $organizationId): int
    {
        if (isset(self::$cache[$organizationId])) {
            return self::$cache[$organizationId];
        }

        $db = Database::getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM contact_messages 
            WHERE organization_id = ? 
              AND status = 'unread' 
              AND deleted_at IS NULL
        ");
        $stmt->execute([$organizationId]);
        $count = (int)$stmt->fetchColumn();

        self::$cache[$organizationId] = $count;
        return $count;
    }

    /**
     * Clear the static memory cache (useful for testing or after status updates).
     */
    public static function clearCache(?string $organizationId = null): void
    {
        if ($organizationId !== null) {
            unset(self::$cache[$organizationId]);
        } else {
            self::$cache = [];
        }
    }
}
