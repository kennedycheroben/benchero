<?php

/**
 * Migration 029 — Add performance index for organization unread contact messages query
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // Check if index already exists
        $stmt = $pdo->query("SHOW INDEX FROM contact_messages WHERE Key_name = 'idx_contact_msg_org_status_deleted'");
        $existing = $stmt->fetchAll();

        if (empty($existing)) {
            $pdo->exec("
                ALTER TABLE contact_messages
                ADD INDEX idx_contact_msg_org_status_deleted (organization_id, status, deleted_at)
            ");
        }
    }
};
