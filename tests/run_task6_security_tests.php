<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Teamora\Core\Database\Database;
use Teamora\Core\Ulid;

$pdo = Database::getConnection();

echo "Starting HTTP Security Tests for Task 6...\n";

// 1. Setup Data for HTTP tests
$testEmail1 = 'httptest1_' . time() . '@example.com';
$testEmail2 = 'httptest2_' . time() . '@example.com';
$password = 'password123';
$passHash = password_hash($password, PASSWORD_DEFAULT);
$id1 = Ulid::generate();
$id2 = Ulid::generate();

$pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$id1', 'HTTP Test Unverified', '$testEmail1', '$passHash', NULL)");
$pdo->exec("INSERT INTO users (id, name, email, password_hash, email_verified_at) VALUES ('$id2', 'HTTP Test Verified', '$testEmail2', '$passHash', NOW())");

$pdo->exec("TRUNCATE TABLE rate_limits");

$baseUrl = 'http://localhost/teamora';

// 1. Get CSRF Token
$ch = curl_init("$baseUrl/login");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$header = substr($response, 0, $header_size);
$body = substr($response, $header_size);
curl_close($ch);

preg_match('/tmsess=([^;]+)/', $header, $matches);
$sessionId = $matches[1] ?? '';
preg_match('/name="_csrf" value="([^"]+)"/', $body, $matches);
$csrfToken = $matches[1] ?? '';

if (!$csrfToken) {
    die("FAILED: Could not retrieve CSRF token from login page.\n");
}
echo " - CSRF token retrieved.\n";

function post($url, $data, $csrf = true, $follow = false, $headerOut = false) {
    global $baseUrl, $sessionId, $csrfToken;
    if ($csrf && $csrfToken) {
        $data['_csrf'] = $csrfToken;
    }
    
    $ch = curl_init("$baseUrl$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIE, "tmsess=$sessionId");
    if ($follow) curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($headerOut) curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $response];
}

$res = post('/login', [
    'email' => $testEmail2,
    'password' => 'wrong'
]);
if ($res['code'] !== 401) {
    die("FAILED: Invalid password did not return 401. Got: {$res['code']}\n");
}
echo " - Invalid credentials rejected (401).\n";

$res = post('/login', [
    'email' => $testEmail1,
    'password' => $password
]);
if ($res['code'] !== 403) {
    die("FAILED: Unverified account did not return 403. Got: {$res['code']}\n");
}
echo " - Unverified account blocked (403).\n";

$res = post('/login', [
    'email' => $testEmail2,
    'password' => $password
], true, false, true);

if ($res['code'] !== 302 || strpos($res['body'], 'onboarding') === false) {
    die("FAILED: Successful login did not redirect to /onboarding. Got: {$res['code']}\n");
}
echo " - Successful login (0 orgs) redirected to /onboarding.\n";

// At this point session ID was regenerated. Let's get new session ID from header.
preg_match('/tmsess=([^;]+)/', $res['body'], $matches);
if (isset($matches[1])) {
    $sessionId = $matches[1];
}

$res = post('/logout', [], true, false, true);
if ($res['code'] !== 302 || strpos($res['body'], 'login') === false) {
    die("FAILED: Logout did not redirect to /login.\n");
}
echo " - Logout successful.\n";

$pdo->exec("DELETE FROM users WHERE email IN ('$testEmail1', '$testEmail2')");

echo "\nAll Task 6 HTTP Tests Passed Successfully!\n";
