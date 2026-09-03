<?php

namespace Benchero\Core\Middleware;

use Benchero\Core\Http\Request;
use Benchero\Core\Http\Response;

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
