<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Ulid;

echo "==================================================\n";
echo " BENCHERO ROLES & CONTACT MESSAGES SUITE\n";
echo "==================================================\n";

$passes = 0;
$fails = 0;

function assertTest(bool $condition, string $description) {
    global $passes, $fails;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passes++;
    } else {
        echo " [FAIL] {$description}\n";
        $fails++;
    }
}

try {
    $db = Database::getConnection();

    // 1. Verify roles table exists and has default roles
    $rolesCount = (int)$db->query("SELECT COUNT(*) FROM roles")->fetchColumn();
    assertTest($rolesCount >= 4, "roles table exists with seeded default roles");

    $superAdminRole = $db->query("SELECT * FROM roles WHERE name = 'super_admin'")->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($superAdminRole) && (int)$superAdminRole['can_view_all_stats'] === 1, "super_admin role has can_view_all_stats = 1");

    $clubOwnerRole = $db->query("SELECT * FROM roles WHERE name = 'club_owner'")->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($clubOwnerRole) && (int)$clubOwnerRole['can_view_all_stats'] === 0, "club_owner role exists with can_view_all_stats = 0");

    // 2. Verify users table has role_id and direct role column
    $userCols = $db->query("SHOW COLUMNS FROM users LIKE 'role'")->fetchAll();
    assertTest(!empty($userCols), "users table has direct 'role' column");

    $userRoleIdCol = $db->query("SHOW COLUMNS FROM users LIKE 'role_id'")->fetchAll();
    assertTest(!empty($userRoleIdCol), "users table has 'role_id' column");

    // 3. Verify contact_messages table exists
    $msgCols = $db->query("SHOW COLUMNS FROM contact_messages LIKE 'organization_id'")->fetchAll();
    assertTest(!empty($msgCols), "contact_messages table exists with organization_id");

    // 4. Test Inserting Club Contact Message
    $testOrg = $db->query("SELECT id, slug FROM organizations WHERE deleted_at IS NULL LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($testOrg) {
        $msgId = Ulid::generate();
        $stmt = $db->prepare("
            INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
            VALUES (?, ?, 'John Fan', 'john@example.com', 'Ticket Inquiry', 'How do I buy season tickets?', 'unread', NOW())
        ");
        $stmt->execute([$msgId, $testOrg['id']]);

        $fetchedMsg = $db->query("SELECT * FROM contact_messages WHERE id = '{$msgId}'")->fetch(PDO::FETCH_ASSOC);
        assertTest(!empty($fetchedMsg) && $fetchedMsg['organization_id'] === $testOrg['id'], "Club contact message correctly saved with organization_id");
        assertTest($fetchedMsg['status'] === 'unread', "Contact message initially created with status 'unread'");

        // Test updating status
        $db->exec("UPDATE contact_messages SET status = 'read' WHERE id = '{$msgId}'");
        $updatedMsg = $db->query("SELECT status FROM contact_messages WHERE id = '{$msgId}'")->fetchColumn();
        assertTest($updatedMsg === 'read', "Contact message status updated to 'read'");

        // Clean up test message
        $db->exec("DELETE FROM contact_messages WHERE id = '{$msgId}'");
    } else {
        assertTest(true, "Skipping org message insert (no test organization present)");
    }

    // 5. Test Inserting Platform Contact Message (organization_id = NULL)
    $platformMsgId = Ulid::generate();
    $stmt = $db->prepare("
        INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
        VALUES (?, NULL, 'Platform Lead', 'lead@example.com', 'Sponsorship Inquiry', 'We want to sponsor Benchero platform', 'unread', NOW())
    ");
    $stmt->execute([$platformMsgId]);

    $fetchedPlatformMsg = $db->query("SELECT * FROM contact_messages WHERE id = '{$platformMsgId}'")->fetch(PDO::FETCH_ASSOC);
    assertTest(!empty($fetchedPlatformMsg) && $fetchedPlatformMsg['organization_id'] === null, "Platform contact message saved with organization_id = NULL");

    // Clean up platform test message
    $db->exec("DELETE FROM contact_messages WHERE id = '{$platformMsgId}'");

    // 6. Test OrganizationService Auto-Unique Slug & Direct User Role Assignment
    $orgService = new \Benchero\Services\OrganizationService();
    $testUserId = $db->query("SELECT id FROM users LIMIT 1")->fetchColumn();
    if ($testUserId) {
        $slug1 = $orgService->createOrganization('Acme Test Club', 'acme-sports-test', 'KE', 'Africa/Nairobi', $testUserId);
        $slug2 = $orgService->createOrganization('Acme Test Club 2', 'acme-sports-test', 'KE', 'Africa/Nairobi', $testUserId);

        assertTest($slug1 === 'acme-sports-test', "First org created with exact requested slug 'acme-sports-test'");
        assertTest($slug2 === 'acme-sports-test-1', "Second org with duplicate slug automatically resolved to 'acme-sports-test-1'");

        $userRole = $db->query("SELECT role FROM users WHERE id = '{$testUserId}'")->fetchColumn();
        assertTest(in_array($userRole, ['owner', 'super_admin']), "User role in users table automatically updated to 'owner'");

        // Clean up test orgs
        $db->exec("DELETE FROM organization_user WHERE organization_id IN (SELECT id FROM organizations WHERE slug IN ('acme-sports-test', 'acme-sports-test-1'))");
        $db->exec("DELETE FROM subscriptions WHERE organization_id IN (SELECT id FROM organizations WHERE slug IN ('acme-sports-test', 'acme-sports-test-1'))");
        $db->exec("DELETE FROM organizations WHERE slug IN ('acme-sports-test', 'acme-sports-test-1')");
    }

    // 7. Test User Role Update
    $testUser = $db->query("SELECT id, role_id, is_platform_admin FROM users LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($testUser && $superAdminRole) {
        $originalRoleId = $testUser['role_id'];
        $originalAdmin = $testUser['is_platform_admin'];

        // Assign super_admin role
        $db->exec("UPDATE users SET role_id = '{$superAdminRole['id']}', is_platform_admin = 1 WHERE id = '{$testUser['id']}'");
        $checkUser = $db->query("SELECT u.role_id, r.name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = '{$testUser['id']}'")->fetch(PDO::FETCH_ASSOC);
        assertTest($checkUser['name'] === 'super_admin', "User successfully updated to super_admin role");

        // Restore user state
        $stmtRestore = $db->prepare("UPDATE users SET role_id = ?, is_platform_admin = ? WHERE id = ?");
        $stmtRestore->execute([$originalRoleId, $originalAdmin, $testUser['id']]);
    } else {
        assertTest(true, "Skipping user role update test (no test user present)");
    }

} catch (Exception $e) {
    echo " [ERROR] " . $e->getMessage() . "\n";
    $fails++;
}

echo "==================================================\n";
echo " SUMMARY: Passed {$passes} / Failed {$fails}\n";
echo "==================================================\n";

exit($fails > 0 ? 1 : 0);
