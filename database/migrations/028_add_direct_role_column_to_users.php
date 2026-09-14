<?php

/**
 * Migration 028 — Add direct `role` column to `users` table
 */

return new class {
    public function up(\PDO $pdo): void
    {
        // 1. Add `role` column to users table if missing
        $cols = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("
                ALTER TABLE users
                ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'member' AFTER email
            ");

            // Update role based on platform admin status or organization owner status
            $pdo->exec("
                UPDATE users u
                SET u.role = 'super_admin'
                WHERE u.is_platform_admin = 1
                   OR u.role_id IN (SELECT id FROM roles WHERE name IN ('super_admin', 'admin'))
            ");

            $pdo->exec("
                UPDATE users u
                SET u.role = 'owner'
                WHERE u.role != 'super_admin'
                  AND u.id IN (SELECT DISTINCT user_id FROM organization_user WHERE role = 'owner')
            ");
        }
    }
};
