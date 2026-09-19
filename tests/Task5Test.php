<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use Benchero\Core\Database\Database;
use Benchero\Services\Auth\AuthService;
use Benchero\Services\Auth\AuthTokenService;
use Exception;

class Task5Test extends TestCase
{
    private $pdo;
    private $authService;
    private $tokenService;

    protected function setUp(): void
    {
        $this->pdo = Database::getConnection();
        $this->pdo->beginTransaction();
        $this->authService = new AuthService($this->pdo);
        $this->tokenService = new AuthTokenService($this->pdo);
    }

    protected function tearDown(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function test_registration_and_verification_flow(): void
    {
        $this->pdo->exec("DELETE FROM users WHERE email = 'testuser@example.com'");
        $this->pdo->exec("DELETE FROM users WHERE email = 'expired@example.com'");

        $userId = $this->authService->registerUser('Test User', 'testuser@example.com', 'password123');
        $this->assertNotNull($userId);

        $dupId = $this->authService->registerUser('Duplicate', 'testuser@example.com', 'password123');
        $this->assertNull($dupId);

        $token = $this->tokenService->generateToken($userId, AuthTokenService::TYPE_EMAIL_VERIFICATION, 86400);
        $this->assertEquals(64, strlen($token));

        $invalidUserId = $this->tokenService->validateAndUseToken('invalid_token_123', AuthTokenService::TYPE_EMAIL_VERIFICATION);
        $this->assertNull($invalidUserId);

        $verifiedUserId = $this->tokenService->validateAndUseToken($token, AuthTokenService::TYPE_EMAIL_VERIFICATION);
        $this->assertEquals($userId, $verifiedUserId);

        $this->authService->markEmailVerified($userId);
        $user = $this->authService->findUserById($userId);
        $this->assertNotNull($user['email_verified_at']);

        $reused = $this->tokenService->validateAndUseToken($token, AuthTokenService::TYPE_EMAIL_VERIFICATION);
        $this->assertNull($reused);

        $expUserId = $this->authService->registerUser('Expired', 'expired@example.com', 'pass');
        $expToken = $this->tokenService->generateToken($expUserId, AuthTokenService::TYPE_EMAIL_VERIFICATION, -3600);
        $expCheck = $this->tokenService->validateAndUseToken($expToken, AuthTokenService::TYPE_EMAIL_VERIFICATION);
        $this->assertNull($expCheck);
    }
}
