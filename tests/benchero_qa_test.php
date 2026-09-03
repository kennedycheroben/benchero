<?php

require __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Http\Request;
use Benchero\Core\Routing\Router;

$router = require __DIR__ . '/../config/routes.php';

function testRoute(Router $router, string $method, string $path, int $expectedStatus, string $label) {
    $_SERVER['REQUEST_METHOD'] = $method;
    $_SERVER['REQUEST_URI'] = '/benchero' . $path;
    $_SERVER['SCRIPT_NAME'] = '/benchero/public/index.php';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

    $request = Request::capture();
    $response = $router->dispatch($request);
    
    $status = $response->getStatusCode();
    if ($status === $expectedStatus) {
        echo "[PASS] {$label}: HTTP {$status}" . PHP_EOL;
    } else {
        echo "[FAIL] {$label}: Expected HTTP {$expectedStatus}, got {$status}" . PHP_EOL;
    }
}

echo "==================================================" . PHP_EOL;
echo "BENCHERO RESTRUCTURE QA TEST SUITE" . PHP_EOL;
echo "==================================================" . PHP_EOL;

testRoute($router, 'GET', '/', 200, 'Public Home Page');
testRoute($router, 'GET', '/about', 200, 'Public About Page');
testRoute($router, 'GET', '/pricing', 200, 'Public Pricing Page');
testRoute($router, 'GET', '/contact', 200, 'Public Contact Page');
testRoute($router, 'GET', '/terms', 200, 'Public Terms Page');
testRoute($router, 'GET', '/privacy', 200, 'Public Privacy Page');
testRoute($router, 'GET', '/forgot-password', 200, 'Forgot Password Form');
testRoute($router, 'GET', '/reset-password/test-token', 200, 'Reset Password Form');
testRoute($router, 'GET', '/nonexistent-benchero-route', 404, '404 Custom Error Page');

echo "BENCHERO QA Test Suite Complete!" . PHP_EOL;
