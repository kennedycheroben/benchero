<?php

/**
 * Migration 034 — Seed Production & Localhost Test Account
 *
 * Ensures cherobenkennedy34@gmail.com exists as the official testing account
 * across both localhost and production environments with:
 * - Super admin role & role_id
 * - Platform admin privileges (is_platform_admin = 1)
 * - Auto-verified email (email_verified_at = NOW())
 * - Dedicated testing organization ("Production Test Club") with active Benchero Pro subscription
 */

use Benchero\Core\Ulid;

return new class {
    public function up(\PDO $pdo): void
    {
        $testEmail = 'cherobenkennedy34@gmail.com';
        $superAdminRoleId = '01J7ROLE0000000000SUPERADM';

        // 1. Verify roles table has Super Admin role
        try {
            $stmt = $pdo->prepare("SELECT id FROM roles WHERE id = ?");
            $stmt->execute([$superAdminRoleId]);
            if (!$stmt->fetch()) {
                $pdo->prepare("
                    INSERT INTO roles (id, name, display_name, description, can_view_all_stats, can_manage_platform_admins, is_protected, created_at, updated_at)
                    VALUES (?, 'super_admin', 'Super Administrator', 'Full platform administrative access', 1, 1, 1, NOW(), NOW())
                ")->execute([$superAdminRoleId]);
            }
        } catch (\Throwable $e) {}

        // 2. Provision or upgrade test user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$testEmail]);
        $existingUser = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($existingUser) {
            $userId = $existingUser['id'];
            $pdo->prepare("
                UPDATE users 
                SET role = 'super_admin', 
                    role_id = ?, 
                    is_platform_admin = 1, 
                    email_verified_at = COALESCE(email_verified_at, NOW()),
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$superAdminRoleId, $userId]);
        } else {
            $userId = Ulid::generate();
            $defaultPassword = 'TestPassword123!';
            $passwordHash = password_hash($defaultPassword, PASSWORD_DEFAULT);

            $pdo->prepare("
                INSERT INTO users (id, name, email, password_hash, role, role_id, is_platform_admin, email_verified_at, created_at, updated_at)
                VALUES (?, 'Kennedy Cheroben', ?, ?, 'super_admin', ?, 1, NOW(), NOW(), NOW())
            ")->execute([$userId, $testEmail, $passwordHash, $superAdminRoleId]);
        }

        // 3. Ensure test organization exists
        $stmt = $pdo->prepare("SELECT id, slug FROM organizations WHERE slug = 'test-club' OR name = 'Production Test Club' LIMIT 1");
        $stmt->execute();
        $org = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$org) {
            $orgId = Ulid::generate();
            $slug = 'test-club';
            $pdo->prepare("
                INSERT INTO organizations (id, name, slug, country, timezone, description, created_at, updated_at)
                VALUES (?, 'Production Test Club', ?, 'KE', 'Africa/Nairobi', 'Official testing club for localhost and production verification.', NOW(), NOW())
            ")->execute([$orgId, $slug]);
        } else {
            $orgId = $org['id'];
        }

        // 4. Link test user as owner of the test organization
        $stmt = $pdo->prepare("SELECT user_id FROM organization_user WHERE organization_id = ? AND user_id = ?");
        $stmt->execute([$orgId, $userId]);
        if (!$stmt->fetch()) {
            $pdo->prepare("
                INSERT INTO organization_user (organization_id, user_id, role, created_at)
                VALUES (?, ?, 'owner', NOW())
            ")->execute([$orgId, $userId]);
        } else {
            $pdo->prepare("
                UPDATE organization_user 
                SET role = 'owner' 
                WHERE organization_id = ? AND user_id = ?
            ")->execute([$orgId, $userId]);
        }

        // 5. Ensure active Benchero Pro subscription for test organization
        $stmt = $pdo->prepare("SELECT id FROM subscriptions WHERE organization_id = ? LIMIT 1");
        $stmt->execute([$orgId]);
        $sub = $stmt->fetch(\PDO::FETCH_ASSOC);

        $now = date('Y-m-d H:i:s');
        $expires = date('Y-m-d H:i:s', strtotime('+5 years'));

        if (!$sub) {
            $subId = Ulid::generate();
            $pdo->prepare("
                INSERT INTO subscriptions (id, organization_id, plan_id, status, starts_at, expires_at, current_period_end, created_at, updated_at)
                VALUES (?, ?, 4, 'active', ?, ?, ?, NOW(), NOW())
            ")->execute([$subId, $orgId, $now, $expires, $expires]);
        } else {
            $pdo->prepare("
                UPDATE subscriptions 
                SET plan_id = 4, 
                    status = 'active', 
                    expires_at = ?, 
                    current_period_end = ?, 
                    updated_at = NOW()
                WHERE id = ?
            ")->execute([$expires, $expires, $sub['id']]);
        }
    }
};
