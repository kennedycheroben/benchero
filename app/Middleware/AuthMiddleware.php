<?php

namespace Teamora\Middleware;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;
use Teamora\Core\Middleware\MiddlewareInterface;

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
