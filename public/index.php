<?php
ini_set("display_errors", 1); ini_set("display_startup_errors", 1); error_reporting(E_ALL);

require_once __DIR__ . '/../app/bootstrap.php';

use Benchero\Core\Http\Request;
use Benchero\Core\Middleware\Pipeline;

/** @var \Benchero\Core\Routing\Router $router */
$router = require __DIR__ . '/../config/routes.php';

$pipeline = new Pipeline();

$response = $pipeline->through([
    Benchero\Middleware\SecurityHeadersMiddleware::class,
    Benchero\Middleware\SessionMiddleware::class,
    Benchero\Middleware\CsrfMiddleware::class,
    Benchero\Middleware\TenantMiddleware::class,
])->then(function (Request $request) use ($router) {
    return $router->dispatch($request);
});

$response->send();
