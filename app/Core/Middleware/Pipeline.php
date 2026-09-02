<?php

namespace Teamora\Core\Middleware;

use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;

class Pipeline
{
    private array $middlewares = [];
    private \Closure $destination;

    public function send(Request $request): self
    {
        return clone $this;
    }

    public function through(array $middlewares): self
    {
        $this->middlewares = $middlewares;
        return $this;
    }

    public function then(\Closure $destination): Response
    {
        $this->destination = $destination;
        $pipeline = array_reduce(
            array_reverse($this->middlewares),
            function ($next, $middleware) {
                return function (Request $request) use ($next, $middleware) {
                    $instance = is_string($middleware) ? new $middleware() : $middleware;
                    return $instance->handle($request, $next);
                };
            },
            function (Request $request) {
                return ($this->destination)($request);
            }
        );

        return $pipeline(Request::capture());
    }
}
