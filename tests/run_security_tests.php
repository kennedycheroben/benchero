<?php

$baseUrl = 'http://localhost/teamora/public';

// 1. Get CSRF Token
$ch = curl_init("$baseUrl/register");
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

echo "Session ID: $sessionId\n";
echo "CSRF Token: $csrfToken\n";

function post($url, $data, $csrf = true) {
    global $baseUrl, $sessionId, $csrfToken;
    if ($csrf && $csrfToken) {
        $data['_csrf'] = $csrfToken;
    }
    
    $ch = curl_init("$baseUrl$url");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_COOKIE, "tmsess=$sessionId");
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return ['code' => $httpCode, 'body' => $response];
}

echo "\n--- CSRF TEST ---\n";
$res = post('/register', [
    'name' => 'No CSRF',
    'email' => 'nocsrf@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
], false);
echo "Expected 403, got: " . $res['code'] . "\n";
if ($res['code'] !== 403) echo "CSRF TEST FAILED!\n";

echo "\n--- VALID REGISTRATION ---\n";
$res = post('/register', [
    'name' => 'Valid User',
    'email' => 'valid@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
]);
echo "Expected 200 (Success message), got: " . $res['code'] . "\n";
if (strpos($res['body'], 'Registration successful') !== false) {
    echo "Registration success message found.\n";
} else {
    echo "REGISTRATION TEST FAILED!\n";
}

echo "\n--- DUPLICATE REGISTRATION ---\n";
$res = post('/register', [
    'name' => 'Valid User',
    'email' => 'valid@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password123'
]);
echo "Expected 200 (Success message), got: " . $res['code'] . "\n";
if (strpos($res['body'], 'Registration successful') !== false) {
    echo "Duplicate registration gives same success message (Anti-enumeration).\n";
} else {
    echo "DUPLICATE REGISTRATION TEST FAILED!\n";
}

echo "\n--- PASSWORD VALIDATION ---\n";
$res = post('/register', [
    'name' => 'Short Pass',
    'email' => 'short@example.com',
    'password' => 'short',
    'password_confirmation' => 'short'
]);
echo "Expected 400 (Short pass), got: " . $res['code'] . "\n";
if (strpos($res['body'], 'Password must be at least 8 characters') !== false) {
    echo "Password too short error found.\n";
} else {
    echo "SHORT PASSWORD TEST FAILED!\n";
}

$res = post('/register', [
    'name' => 'Mismatch Pass',
    'email' => 'mismatch@example.com',
    'password' => 'password123',
    'password_confirmation' => 'password321'
]);
echo "Expected 400 (Mismatch pass), got: " . $res['code'] . "\n";
if (strpos($res['body'], 'Passwords do not match') !== false) {
    echo "Password mismatch error found.\n";
} else {
    echo "MISMATCH PASSWORD TEST FAILED!\n";
}

echo "\nDone!\n";
