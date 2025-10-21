<?php

declare(strict_types=1);

namespace App\Routing;

use App\Http\Exceptions\HttpException;
use App\Http\JsonResponse;
use App\Http\Request;
use App\Http\Response;
use App\Middleware\MiddlewareInterface;
use JsonException;

final class Router
{
    /** @var array<string, Route[]> */
    private array $routes = [];

    /**
     * @param callable(Request, array<string, string>): Response $handler
     * @param MiddlewareInterface[] $middleware
     */
    public function add(string $method, string $path, callable $handler, array $middleware = []): void
    {
        $method = strtoupper($method);
        $this->routes[$method] ??= [];
        $this->routes[$method][] = new Route($method, $path, $handler, $middleware);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function get(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('GET', $path, $handler, $middleware);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function post(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('POST', $path, $handler, $middleware);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function put(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('PUT', $path, $handler, $middleware);
    }

    /**
     * @param MiddlewareInterface[] $middleware
     */
    public function delete(string $path, callable $handler, array $middleware = []): void
    {
        $this->add('DELETE', $path, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->getMethod();
        $path = $request->getPath();

        foreach ($this->routes[$method] ?? [] as $route) {
            if (!$route->matches($method, $path, $parameters)) {
                continue;
            }

            $handler = $this->buildPipeline($route, $parameters);

            try {
                return $handler($request);
            } catch (JsonException $exception) {
                throw new HttpException(400, 'Invalid JSON payload.', previous: $exception);
            }
        }

        throw new HttpException(404, 'Resource not found.');
    }

    /**
     * @return callable(Request): Response
     */
    private function buildPipeline(Route $route, array $parameters): callable
    {
        $handler = static function (Request $request) use ($route, $parameters): Response {
            $requestWithParameters = $request->withAttribute('routeParameters', $parameters);
            $callable = $route->getHandler();

            $response = $callable($requestWithParameters, $parameters);

            if ($response instanceof Response) {
                return $response;
            }

            return JsonResponse::fromArray((array)$response);
        };

        $middlewareStack = array_reverse($route->getMiddleware());

        foreach ($middlewareStack as $middleware) {
            $handler = static function (Request $request) use ($middleware, $handler): Response {
                return $middleware->process($request, $handler);
            };
        }

        return $handler;
    }
}
