<?php

/**
 * Benchero — Test Account Provisioning CLI
 *
 * Usage:
 *   php bin/seed_test_account.php [password]
 *
 * Example:
 *   php bin/seed_test_account.php "MySecurePass123!"
 */

require_once __DIR__ . '/../app/bootstrap.php';
require_once __DIR__ . '/../app/Core/helpers.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

$testEmail = 'cherobenkennedy34@gmail.com';
$password = $argv[1] ?? 'TestPassword123!';
$superAdminRoleId = '01J7ROLE0000000000SUPERADM';

echo "=====================================================\n";
echo " BENCHERO TEST ACCOUNT PROVISIONING\n";
echo " Target: {$testEmail}\n";
echo " Environment: " . env('APP_ENV') . "\n";
echo "=====================================================\n\n";

$db = Database::getConnection();

// 1. Ensure Super Admin role exists
$stmt = $db->prepare("SELECT id FROM roles WHERE id = ?");
$stmt->execute([$superAdminRoleId]);
if (!$stmt->fetch()) {
    $db->prepare("
        INSERT INTO roles (id, name, display_name, description, can_view_all_stats, can_manage_platform_admins, is_protected, created_at, updated_at)
        VALUES (?, 'super_admin', 'Super Administrator', 'Full platform administrative access', 1, 1, 1, NOW(), NOW())
    ")->execute([$superAdminRoleId]);
    echo " [+] Created Super Admin role ({$superAdminRoleId})\n";
}

// 2. Create or update user
$stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$hash = password_hash($password, PASSWORD_DEFAULT);

if ($user) {
    $userId = $user['id'];
    $stmt = $db->prepare("
        UPDATE users 
        SET password_hash = ?, 
            role = 'super_admin', 
            role_id = ?, 
            is_platform_admin = 1, 
            email_verified_at = COALESCE(email_verified_at, NOW()),
            updated_at = NOW() 
        WHERE id = ?
    ");
    $stmt->execute([$hash, $superAdminRoleId, $userId]);
    echo " [+] Updated user {$testEmail} (ID: {$userId})\n";
} else {
    $userId = Ulid::generate();
    $stmt = $db->prepare("
        INSERT INTO users (id, name, email, password_hash, role, role_id, is_platform_admin, email_verified_at, created_at, updated_at)
        VALUES (?, 'Kennedy Cheroben', ?, ?, 'super_admin', ?, 1, NOW(), NOW(), NOW())
    ");
    $stmt->execute([$userId, $testEmail, $hash, $superAdminRoleId]);
    echo " [+] Created user {$testEmail} (ID: {$userId})\n";
}

// 3. Ensure test organization exists
$stmt = $db->prepare("SELECT id, slug FROM organizations WHERE slug = 'test-club' OR name = 'Production Test Club' LIMIT 1");
$stmt->execute();
$org = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$org) {
    $orgId = Ulid::generate();
    $slug = 'test-club';
    $db->prepare("
        INSERT INTO organizations (id, name, slug, country, timezone, description, created_at, updated_at)
        VALUES (?, 'Production Test Club', ?, 'KE', 'Africa/Nairobi', 'Official testing club for localhost and production verification.', NOW(), NOW())
    ")->execute([$orgId, $slug]);
    echo " [+] Created organization 'Production Test Club' (Slug: {$slug})\n";
} else {
    $orgId = $org['id'];
    echo " [+] Found organization 'Production Test Club' (Slug: {$org['slug']})\n";
}

// 4. Link owner
$stmt = $db->prepare("SELECT user_id FROM organization_user WHERE organization_id = ? AND user_id = ?");
$stmt->execute([$orgId, $userId]);
if (!$stmt->fetch()) {
    $db->prepare("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES (?, ?, 'owner', NOW())")->execute([$orgId, $userId]);
    echo " [+] Linked user as club owner\n";
}

// 5. Ensure 5-year active Benchero Pro subscription
$expires = date('Y-m-d H:i:s', strtotime('+5 years'));
$stmt = $db->prepare("SELECT id FROM subscriptions WHERE organization_id = ? LIMIT 1");
$stmt->execute([$orgId]);
$sub = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sub) {
    $subId = Ulid::generate();
    $db->prepare("
        INSERT INTO subscriptions (id, organization_id, plan_id, status, starts_at, expires_at, current_period_end, created_at, updated_at)
        VALUES (?, ?, 4, 'active', NOW(), ?, ?, NOW(), NOW())
    ")->execute([$subId, $orgId, $expires, $expires]);
    echo " [+] Created active Benchero Pro subscription (expires: {$expires})\n";
} else {
    $db->prepare("
        UPDATE subscriptions 
        SET plan_id = 4, status = 'active', expires_at = ?, current_period_end = ?, updated_at = NOW() 
        WHERE id = ?
    ")->execute([$expires, $expires, $sub['id']]);
    echo " [+] Renewed Benchero Pro subscription (expires: {$expires})\n";
}

echo "\n-----------------------------------------------------\n";
echo " SUCCESS: Test account is fully ready!\n";
echo " Login Email: {$testEmail}\n";
echo " Password:    {$password}\n";
echo " Role:        Super Administrator (is_platform_admin=1)\n";
echo " Org URL:     /o/" . ($org['slug'] ?? 'test-club') . "\n";
echo " Admin URL:   /admin\n";
echo "-----------------------------------------------------\n";
