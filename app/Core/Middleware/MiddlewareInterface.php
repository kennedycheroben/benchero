<?php

namespace Teamora\Core\Middleware;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;

interface MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function handle(Request $request, callable $next): Response;
}
