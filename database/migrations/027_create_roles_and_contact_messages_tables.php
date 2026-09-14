<?php

/**
 * Migration 027 — Create Roles & Contact Messages Tables, and Link Roles to Users
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Create roles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS roles (
                id CHAR(26) NOT NULL PRIMARY KEY,
                name VARCHAR(50) NOT NULL UNIQUE,
                display_name VARCHAR(100) NOT NULL,
                description VARCHAR(255) NULL,
                can_view_all_stats TINYINT(1) NOT NULL DEFAULT 0,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Seed default roles if empty
        $roleCount = (int)$pdo->query("SELECT COUNT(*) FROM roles")->fetchColumn();
        if ($roleCount === 0) {
            $stmt = $pdo->prepare("INSERT INTO roles (id, name, display_name, description, can_view_all_stats) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['01J7ROLE0000000000SUPERADM', 'super_admin', 'Super Admin', 'Full platform control, roles management, and access to all website statistics', 1]);
            $stmt->execute(['01J7ROLE0000000000PLATFADM', 'admin', 'Platform Admin', 'Platform administrator with access to view all website statistics', 1]);
            $stmt->execute(['01J7ROLE0000000000CLUBO WN', 'club_owner', 'Club Owner', 'Organization / Club owner with management rights over assigned club', 0]);
            $stmt->execute(['01J7ROLE0000000000STDUSER', 'member', 'Member', 'Standard platform user or club member', 0]);
        }

        // 2. Add role_id to users table if missing
        $userCols = $pdo->query("SHOW COLUMNS FROM users LIKE 'role_id'")->fetchAll();
        if (empty($userCols)) {
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN role_id CHAR(26) NULL AFTER is_platform_admin,
                ADD CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE SET NULL
            ");

            // Fetch role IDs
            $roles = $pdo->query("SELECT id, name FROM roles")->fetchAll(\PDO::FETCH_KEY_PAIR);
            $superAdminRoleId = $roles['super_admin'] ?? null;
            $clubOwnerRoleId = $roles['club_owner'] ?? null;

            // Migrate existing users
            if ($superAdminRoleId) {
                $pdo->exec("UPDATE users SET role_id = '{$superAdminRoleId}' WHERE is_platform_admin = 1");
            }
            if ($clubOwnerRoleId) {
                $pdo->exec("UPDATE users SET role_id = '{$clubOwnerRoleId}' WHERE (is_platform_admin IS NULL OR is_platform_admin = 0) AND role_id IS NULL");
            }
        }

        // 3. Create contact_messages table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS contact_messages (
                id CHAR(26) NOT NULL PRIMARY KEY,
                organization_id CHAR(26) NULL DEFAULT NULL,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                subject VARCHAR(255) NULL,
                message TEXT NOT NULL,
                status ENUM('unread', 'read', 'archived') NOT NULL DEFAULT 'unread',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                deleted_at TIMESTAMP NULL DEFAULT NULL,
                KEY fk_contact_msg_org (organization_id),
                KEY idx_contact_msg_status (status),
                KEY idx_contact_msg_created (created_at),
                CONSTRAINT fk_contact_msg_org FOREIGN KEY (organization_id) REFERENCES organizations (id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
};
