<?php

namespace Benchero\Middleware;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;
use Benchero\Core\Middleware\MiddlewareInterface;

class AuthMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        if (empty($_SESSION['_user_id'])) {
            return Response::redirect('/login');
        }

        return $next($request);
    }
}
