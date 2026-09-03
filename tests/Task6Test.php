<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Services\Auth\AuthService;
use Benchero\Core\Ulid;

$pdo = Database::getConnection();
$authService = new AuthService($pdo);

echo "Starting Task 6 Tests...\n";

$pdo->beginTransaction();

try {
    // Setup
    $userId0 = Ulid::generate();
    $userId1 = Ulid::generate();
    $userId2 = Ulid::generate();
    $org1 = Ulid::generate();
    $org2 = Ulid::generate();
    $passHash = password_hash('password123', PASSWORD_DEFAULT);

    $slug1 = 'test-org-1-' . Ulid::generate();
    $slug2 = 'test-org-2-' . Ulid::generate();

    $pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                VALUES ('$userId0', 'User 0 Orgs', 'user0@example.com', '$passHash', NOW(), NOW(), NOW())");
    $pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                VALUES ('$userId1', 'User 1 Org', 'user1@example.com', '$passHash', NOW(), NOW(), NOW())");
    $pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                VALUES ('$userId2', 'User 2 Orgs', 'user2@example.com', '$passHash', NOW(), NOW(), NOW())");
                
    $pdo->exec("INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES ('$org1', '$slug1', 'Org 1', NOW(), NOW())");
    $pdo->exec("INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES ('$org2', '$slug2', 'Org 2', NOW(), NOW())");
    
    $pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org1', '$userId1', 'member', NOW())");
    $pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org1', '$userId2', 'member', NOW())");
    $pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org2', '$userId2', 'member', NOW())");

    echo "1. Testing Auth Service...\n";

    // Valid credentials
    $user = $authService->findUserByEmail('user1@example.com');
    if (!$user) throw new Exception("FAILED: User not found.");
    if (!$authService->verifyPassword('password123', $user['password_hash'])) throw new Exception("FAILED: Password verification failed.");
    echo " - Valid credentials verified.\n";

    // Invalid credentials
    if ($authService->verifyPassword('wrongpass', $user['password_hash'])) throw new Exception("FAILED: Invalid password accepted.");
    echo " - Invalid credentials rejected.\n";

    // Update last login
    $authService->updateLastLogin($userId1);
    $userUpdated = $authService->findUserByEmail('user1@example.com');
    if ($userUpdated['last_login_at'] === null) throw new Exception("FAILED: last_login_at not updated.");
    echo " - last_login_at updated successfully.\n";

    echo "2. Testing Tenant Routing Logic...\n";
    
    $orgs0 = $authService->getUserOrganizations($userId0);
    if (count($orgs0) !== 0) throw new Exception("FAILED: Expected 0 orgs.");
    echo " - User with 0 organizations handled.\n";

    $orgs1 = $authService->getUserOrganizations($userId1);
    if (count($orgs1) !== 1 || $orgs1[0]['slug'] !== $slug1) throw new Exception("FAILED: Expected 1 org.");
    echo " - User with 1 organization handled.\n";

    $orgs2 = $authService->getUserOrganizations($userId2);
    if (count($orgs2) !== 2) throw new Exception("FAILED: Expected 2 orgs.");
    echo " - User with 2 organizations handled.\n";

    echo "\nAll Task 6 Unit Tests Passed Successfully!\n";
} catch (Exception $e) {
    echo "\n" . $e->getMessage() . "\n";
} finally {
    $pdo->rollBack();
    echo " - Test data rolled back.\n";
}
