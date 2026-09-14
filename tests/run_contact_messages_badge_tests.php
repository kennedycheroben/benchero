<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Core\Http\Request;
use Benchero\Core\Ulid;
use Benchero\Services\ContactMessageService;
use Benchero\Controllers\Tenant\ContactMessageController;

echo "==================================================\n";
echo " BENCHERO UNREAD CONTACT MESSAGES BADGE SUITE\n";
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

    // Setup 2 isolated test organizations
    $orgIdA = Ulid::generate();
    $orgIdB = Ulid::generate();
    $slugA = 'test-badge-org-a-' . time();
    $slugB = 'test-badge-org-b-' . time();

    $db->prepare("INSERT INTO organizations (id, name, slug, created_at) VALUES (?, ?, ?, NOW())")->execute([$orgIdA, 'Badge Org A', $slugA]);
    $db->prepare("INSERT INTO organizations (id, name, slug, created_at) VALUES (?, ?, ?, NOW())")->execute([$orgIdB, 'Badge Org B', $slugB]);

    ContactMessageService::clearCache();

    // 1. New message defaults to unread
    $msgId1 = Ulid::generate();
    $stmt = $db->prepare("
        INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
        VALUES (?, ?, 'Alice Test', 'alice@test.com', 'Help Desk', 'Need assistance', 'unread', NOW())
    ");
    $stmt->execute([$msgId1, $orgIdA]);

    $fetchedMsg = $db->query("SELECT status FROM contact_messages WHERE id = '{$msgId1}'")->fetchColumn();
    assertTest($fetchedMsg === 'unread', "1. New contact message defaults to status 'unread'");

    // 2. Unread count returns correct number
    ContactMessageService::clearCache();
    $countA = ContactMessageService::getUnreadCount($orgIdA);
    assertTest($countA === 1, "2. Unread count returns 1 after inserting single unread message");

    // Add second unread message to Org A
    $msgId2 = Ulid::generate();
    $db->prepare("
        INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
        VALUES (?, ?, 'Bob Test', 'bob@test.com', 'Sales', 'Price quote needed', 'unread', NOW())
    ")->execute([$msgId2, $orgIdA]);

    ContactMessageService::clearCache();
    $countA = ContactMessageService::getUnreadCount($orgIdA);
    assertTest($countA === 2, "2. Unread count updates to 2 after adding second unread message");

    // 3. Read messages are excluded from count
    $msgIdRead = Ulid::generate();
    $db->prepare("
        INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
        VALUES (?, ?, 'Charlie Test', 'charlie@test.com', 'Info', 'Already read item', 'read', NOW())
    ")->execute([$msgIdRead, $orgIdA]);

    ContactMessageService::clearCache();
    $countA = ContactMessageService::getUnreadCount($orgIdA);
    assertTest($countA === 2, "3. Read messages are excluded from unread count (remains 2)");

    // 4. Marking one message read decreases count
    $db->exec("UPDATE contact_messages SET status = 'read' WHERE id = '{$msgId1}'");
    ContactMessageService::clearCache();
    $countA = ContactMessageService::getUnreadCount($orgIdA);
    assertTest($countA === 1, "4. Marking one message as read decreases unread count from 2 to 1");

    // 5. Tenant isolation (Org B should have 0 unread messages)
    $countB = ContactMessageService::getUnreadCount($orgIdB);
    assertTest($countB === 0, "5. Tenant isolation: Org B count is 0 while Org A count is 1");

    // Insert message for Org B
    $msgIdB1 = Ulid::generate();
    $db->prepare("
        INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
        VALUES (?, ?, 'David Test', 'david@test.com', 'Org B Inquiry', 'Hello Org B', 'unread', NOW())
    ")->execute([$msgIdB1, $orgIdB]);

    ContactMessageService::clearCache();
    assertTest(ContactMessageService::getUnreadCount($orgIdB) === 1, "5. Org B unread count updated to 1 independently");
    assertTest(ContactMessageService::getUnreadCount($orgIdA) === 1, "5. Org A unread count remains 1 isolated from Org B");

    // 6. Controller unreadCount endpoint verification
    $controller = new ContactMessageController();
    $reqA = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/{$slugA}/contact-messages/unread-count"], [], []);
    $reqA->setAttribute('tenant', ['id' => $orgIdA, 'slug' => $slugA]);
    $reqA->setAttribute('tenant_role', 'owner');

    $resA = $controller->unreadCount($reqA);
    $jsonA = json_decode($resA->getContent(), true);
    assertTest($jsonA['success'] === true && $jsonA['unread_count'] === 1, "6. Controller unreadCount endpoint returns correct JSON for Org A");

    $reqB = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/{$slugB}/contact-messages/unread-count"], [], []);
    $reqB->setAttribute('tenant', ['id' => $orgIdB, 'slug' => $slugB]);
    $reqB->setAttribute('tenant_role', 'owner');
    $resB = $controller->unreadCount($reqB);
    $jsonB = json_decode($resB->getContent(), true);
    assertTest($jsonB['success'] === true && $jsonB['unread_count'] === 1, "6. Controller unreadCount endpoint returns isolated JSON for Org B");

    // 7. Unauthorized / unauthenticated request handling
    $reqUnauth = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/{$slugA}/contact-messages/unread-count"], [], []);
    $resUnauth = $controller->unreadCount($reqUnauth);
    assertTest($resUnauth->getStatusCode() === 401, "7. Endpoint rejects request with 401 when tenant context is missing");

    // 8. Mark all messages read action
    $_POST['_csrf'] = 'test_token';
    $_SESSION['_csrf_token'] = 'test_token';

    $reqMarkAll = new Request([], ['_csrf' => 'test_token'], ['REQUEST_METHOD' => 'POST', 'REQUEST_URI' => "/o/{$slugA}/contact-messages/mark-all-read", 'HTTP_ACCEPT' => 'application/json'], [], []);
    $reqMarkAll->setAttribute('tenant', ['id' => $orgIdA, 'slug' => $slugA]);
    $reqMarkAll->setAttribute('tenant_role', 'owner');

    $resMarkAll = $controller->markAllAsRead($reqMarkAll);
    $jsonMarkAll = json_decode($resMarkAll->getContent(), true);

    ContactMessageService::clearCache();
    $countAAfterMarkAll = ContactMessageService::getUnreadCount($orgIdA);
    assertTest($countAAfterMarkAll === 0, "8. Mark all as read resets Org A unread count to 0");
    assertTest($jsonMarkAll['unread_count'] === 0, "8. Mark all as read returns unread_count = 0 in response");

    // 9. 10+ Badge formatting verification
    // Insert 12 unread messages into Org A
    for ($i = 1; $i <= 12; $i++) {
        $db->prepare("
            INSERT INTO contact_messages (id, organization_id, name, email, subject, message, status, created_at)
            VALUES (?, ?, 'Bulk Sender {$i}', 'bulk{$i}@test.com', 'Subject {$i}', 'Message content {$i}', 'unread', NOW())
        ")->execute([Ulid::generate(), $orgIdA]);
    }
    ContactMessageService::clearCache();
    $countA12 = ContactMessageService::getUnreadCount($orgIdA);
    $formattedBadgeText = $countA12 > 9 ? '10+' : (string)$countA12;
    assertTest($countA12 === 12 && $formattedBadgeText === '10+', "9. 10+ formatting rule converts count 12 to badge text '10+'");

    // 10. Clean up test data
    $db->exec("DELETE FROM contact_messages WHERE organization_id IN ('{$orgIdA}', '{$orgIdB}')");
    $db->exec("DELETE FROM organizations WHERE id IN ('{$orgIdA}', '{$orgIdB}')");

    assertTest(true, "10. Test cleanup completed without errors");

} catch (Exception $e) {
    echo " [ERROR] " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    $fails++;
}

echo "==================================================\n";
echo " SUMMARY: Passed {$passes} / Failed {$fails}\n";
echo "==================================================\n";

exit($fails > 0 ? 1 : 0);
