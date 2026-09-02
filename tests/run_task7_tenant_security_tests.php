<?php
require_once __DIR__ . '/../app/bootstrap.php';

use Teamora\Core\Database\Database;
use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Middleware\TenantMiddleware;
use Teamora\Services\OrganizationService;
use Teamora\Core\Ulid;

$db = Database::getConnection();
$db->exec("DELETE FROM subscriptions");
$db->exec("DELETE FROM organization_user");
$db->exec("DELETE FROM organizations");
$db->exec("DELETE FROM users");

function logResult($testName, $passed, $message = '') {
    $status = $passed ? "\e[32mPASS\e[0m" : "\e[31mFAIL\e[0m";
    echo "[$status] $testName" . ($message ? " - $message" : "") . "\n";
    if (!$passed) exit(1);
}

// 1. Create 2 test users
$userA = Ulid::generate();
$userB = Ulid::generate();
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userA', 'User A', 'usera@example.com', 'pwd', NOW())");
$db->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$userB', 'User B', 'userb@example.com', 'pwd', NOW())");

// 2. Test Onboarding and Trial Initialization
$orgService = new OrganizationService();
try {
    $slugA = $orgService->createOrganization('Org A', 'org-a', 'KE', 'Africa/Nairobi', $userA);
    logResult('Organization creation', true, 'Org A created successfully');
} catch (Exception $e) {
    logResult('Organization creation', false, $e->getMessage());
}

// 3. Test Trial Initialization
$stmt = $db->prepare("SELECT * FROM subscriptions s JOIN organizations o ON s.organization_id = o.id WHERE o.slug = 'org-a'");
$stmt->execute();
$sub = $stmt->fetch();
logResult('Trial Initialization', $sub && $sub['status'] === 'trialing', 'Subscription is trialing');

// 4. Test Duplicate Slug
try {
    $orgService->createOrganization('Org A 2', 'org-a', 'US', 'America/New_York', $userB);
    logResult('Duplicate Slug Protection', false, 'Allowed duplicate slug');
} catch (Exception $e) {
    logResult('Duplicate Slug Protection', true, 'Duplicate rejected correctly');
}

// Create Org B for User B
$slugB = $orgService->createOrganization('Org B', 'org-b', 'US', 'America/New_York', $userB);

// 5. Test Cross-Tenant Security with TenantMiddleware
$middleware = new TenantMiddleware();

// Helper to simulate request
function runMiddlewareTest($middleware, $slug, $userId) {
    $_SESSION['_user_id'] = $userId;
    
    // Create a mock Request
    $request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/$slug/dashboard"], [], []);
    
    $response = clone $middleware->handle($request, function($req) {
        return new Response('200 OK', 200); // Controller reached
    });
    
    return $response;
}

// User A accesses Org A -> should allow (200)
$res = runMiddlewareTest($middleware, 'org-a', $userA);
logResult('User A -> Org A', $res->getStatusCode() === 200, 'Access granted');

// User B accesses Org B -> should allow (200)
$res = runMiddlewareTest($middleware, 'org-b', $userB);
logResult('User B -> Org B', $res->getStatusCode() === 200, 'Access granted');

// User A accesses Org B -> should block (403 or 404)
$res = runMiddlewareTest($middleware, 'org-b', $userA);
logResult('User A -> Org B (Cross-Tenant)', $res->getStatusCode() === 403, 'Blocked with 403 (Actual: ' . $res->getStatusCode() . ')');

// User B accesses Org A -> should block (403)
$res = runMiddlewareTest($middleware, 'org-a', $userB);
logResult('User B -> Org A (Cross-Tenant)', $res->getStatusCode() === 403, 'Blocked with 403');

// 6. Test Unauthenticated access
$_SESSION['_user_id'] = null;
$request = new Request([], [], ['REQUEST_METHOD' => 'GET', 'REQUEST_URI' => "/o/org-a/dashboard"], [], []);
$res = $middleware->handle($request, function(){});
logResult('Unauthenticated -> Org A', $res->getStatusCode() === 302, 'Redirected to /login');

echo "\nAll Task 7 security tests passed.\n";
