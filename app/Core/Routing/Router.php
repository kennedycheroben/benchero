<?php

namespace Teamora\Core\Routing;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Teamora\Core\Http\Request;
use Teamora\Core\Http\Response;

class Router
{
    private array $routes = [];

    public function addRoute(string $method, string $route, mixed $handler): void
    {
        $this->routes[] = [$method, $route, $handler];
    }

    public function dispatch(Request $request): Response
    {
        $dispatcher = simpleDispatcher(function(RouteCollector $r) {
            foreach ($this->routes as $route) {
                $r->addRoute($route[0], $route[1], $route[2]);
            }
        });

        $httpMethod = $request->method();
        $uri = $request->path();

        $routeInfo = $dispatcher->dispatch($httpMethod, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::NOT_FOUND:
                return new Response('404 Not Found', 404);
            case Dispatcher::METHOD_NOT_ALLOWED:
                $allowedMethods = $routeInfo[1];
                return new Response('405 Method Not Allowed', 405);
            case Dispatcher::FOUND:
                $handler = $routeInfo[1];
                $vars = $routeInfo[2];
                return $this->callHandler($handler, $vars, $request);
            default:
                return new Response('500 Internal Server Error', 500);
        }
    }

    private function callHandler(mixed $handler, array $vars, Request $request): Response
    {
        if (is_callable($handler)) {
            return call_user_func($handler, $request, $vars);
        }

        if (is_array($handler) && count($handler) === 2) {
            $class = $handler[0];
            $method = $handler[1];
            $controller = new $class();

            $ref = new \ReflectionMethod($class, $method);
            $params = $ref->getParameters();
            if (count($params) >= 2) {
                $paramType = $params[1]->getType();
                if ($paramType && $paramType->getName() === 'array') {
                    return call_user_func([$controller, $method], $request, $vars);
                }
            }

            return call_user_func([$controller, $method], $request, ...array_values($vars));
        }

        return new Response('Invalid route handler', 500);
    }
}
