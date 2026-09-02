<?php

require_once __DIR__ . '/../app/bootstrap.php';

use Teamora\Core\Http\Request;
use Teamora\Core\Middleware\Pipeline;

/** @var \Teamora\Core\Routing\Router $router */
$router = require __DIR__ . '/../config/routes.php';

$pipeline = new Pipeline();

$response = $pipeline->through([
    Teamora\Middleware\SecurityHeadersMiddleware::class,
    Teamora\Middleware\SessionMiddleware::class,
    Teamora\Middleware\CsrfMiddleware::class,
    Teamora\Middleware\TenantMiddleware::class,
])->then(function (Request $request) use ($router) {
    return $router->dispatch($request);
});

$response->send();
