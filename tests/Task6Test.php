<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Benchero\Core\Database\Database;
use Benchero\Services\Auth\AuthService;
use Benchero\Core\Ulid;
use Exception;

class Task6Test extends TestCase
{
    private $pdo;
    private $authService;

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->pdo->beginTransaction();
        $this->authService = new AuthService($this->pdo);
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function test_auth_service_and_tenant_routing(): void
    {
        $userId0 = Ulid::generate();
        $userId1 = Ulid::generate();
        $userId2 = Ulid::generate();
        $org1 = Ulid::generate();
        $org2 = Ulid::generate();
        $passHash = password_hash('password123', PASSWORD_DEFAULT);

        $slug1 = 'test-org-1-' . Ulid::generate();
        $slug2 = 'test-org-2-' . Ulid::generate();

        $this->pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                    VALUES ('$userId0', 'User 0 Orgs', 'user0@example.com', '$passHash', NOW(), NOW(), NOW())");
        $this->pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                    VALUES ('$userId1', 'User 1 Org', 'user1@example.com', '$passHash', NOW(), NOW(), NOW())");
        $this->pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at, created_at, updated_at) 
                    VALUES ('$userId2', 'User 2 Orgs', 'user2@example.com', '$passHash', NOW(), NOW(), NOW())");
                    
        $this->pdo->exec("INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES ('$org1', '$slug1', 'Org 1', NOW(), NOW())");
        $this->pdo->exec("INSERT INTO organizations (id, slug, name, created_at, updated_at) VALUES ('$org2', '$slug2', 'Org 2', NOW(), NOW())");
        
        $this->pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org1', '$userId1', 'member', NOW())");
        $this->pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org1', '$userId2', 'member', NOW())");
        $this->pdo->exec("INSERT INTO organization_user (organization_id, user_id, role, created_at) VALUES ('$org2', '$userId2', 'member', NOW())");

        $user = $this->authService->findUserByEmail('user1@example.com');
        $this->assertNotNull($user);
        $this->assertTrue($this->authService->verifyPassword('password123', $user['password_hash']));

        $this->assertFalse($this->authService->verifyPassword('wrongpass', $user['password_hash']));

        $this->authService->updateLastLogin($userId1);
        $userUpdated = $this->authService->findUserByEmail('user1@example.com');
        $this->assertNotNull($userUpdated['last_login_at']);

        $orgs0 = $this->authService->getUserOrganizations($userId0);
        $this->assertCount(0, $orgs0);

        $orgs1 = $this->authService->getUserOrganizations($userId1);
        $this->assertCount(1, $orgs1);
        $this->assertEquals($slug1, $orgs1[0]['slug']);

        $orgs2 = $this->authService->getUserOrganizations($userId2);
        $this->assertCount(2, $orgs2);
    }
}
