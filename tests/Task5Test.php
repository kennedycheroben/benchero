<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Database\Database;
use Benchero\Services\Auth\AuthService;
use Benchero\Services\Auth\AuthTokenService;

$pdo = Database::getConnection();
$authService = new AuthService($pdo);
$tokenService = new AuthTokenService($pdo);

echo "Starting Task 5 Tests...\n";

$pdo->beginTransaction();

try {
    $pdo->exec("DELETE FROM users WHERE email = 'testuser@example.com'");
    $pdo->exec("DELETE FROM users WHERE email = 'expired@example.com'");

    echo "1. Testing Registration...\n";
    $userId = $authService->registerUser('Test User', 'testuser@example.com', 'password123');
    if (!$userId) throw new Exception("FAILED: Could not register user.");
    echo " - User created: $userId\n";

    $dupId = $authService->registerUser('Duplicate', 'testuser@example.com', 'password123');
    if ($dupId !== null) throw new Exception("FAILED: Allowed duplicate email.");
    echo " - Duplicate email blocked.\n";

    $token = $tokenService->generateToken($userId, AuthTokenService::TYPE_EMAIL_VERIFICATION, 86400);
    if (strlen($token) !== 64) throw new Exception("FAILED: Token length invalid.");
    echo " - Token generated.\n";

    $invalidUserId = $tokenService->validateAndUseToken('invalid_token_123', AuthTokenService::TYPE_EMAIL_VERIFICATION);
    if ($invalidUserId !== null) throw new Exception("FAILED: Allowed invalid token.");
    echo " - Invalid token blocked.\n";

    $verifiedUserId = $tokenService->validateAndUseToken($token, AuthTokenService::TYPE_EMAIL_VERIFICATION);
    if ($verifiedUserId !== $userId) throw new Exception("FAILED: Valid token did not return correct user ID.");
    
    $authService->markEmailVerified($userId);
    $user = $authService->findUserById($userId);
    if ($user['email_verified_at'] === null) throw new Exception("FAILED: email_verified_at not updated.");
    echo " - Token validated and user verified.\n";

    $reused = $tokenService->validateAndUseToken($token, AuthTokenService::TYPE_EMAIL_VERIFICATION);
    if ($reused !== null) throw new Exception("FAILED: Token was reused.");
    echo " - Token reuse prevented.\n";

    $expUserId = $authService->registerUser('Expired', 'expired@example.com', 'pass');
    $expToken = $tokenService->generateToken($expUserId, AuthTokenService::TYPE_EMAIL_VERIFICATION, -3600);
    $expCheck = $tokenService->validateAndUseToken($expToken, AuthTokenService::TYPE_EMAIL_VERIFICATION);
    if ($expCheck !== null) throw new Exception("FAILED: Expired token was accepted.");
    echo " - Expired token blocked.\n";

    echo "\nAll Tests Passed Successfully!\n";
} catch (Exception $e) {
    echo "\n" . $e->getMessage() . "\n";
} finally {
    $pdo->rollBack();
    echo " - Test data rolled back.\n";
}
