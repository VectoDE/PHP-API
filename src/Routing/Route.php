<?php

declare(strict_types=1);

namespace App\Routing;

use App\Http\Request;
use App\Http\Response;
use App\Middleware\MiddlewareInterface;

final class Route
{
    /** @var string[] */
    private array $parameterNames = [];

    /**
     * @param callable(Request, array<string, string>): Response $handler
     * @param MiddlewareInterface[] $middleware
     */
    public function __construct(
        private readonly string $method,
        private readonly string $path,
        private $handler,
        private readonly array $middleware = []
    ) {
    }

    public function matches(string $method, string $path, ?array &$parameters = null): bool
    {
        if (strtoupper($method) !== $this->method) {
            return false;
        }

        $pattern = $this->compilePathToRegex($this->path);

        if (!preg_match($pattern, $path, $matches)) {
            return false;
        }

        $parameters = [];
        foreach ($this->parameterNames as $name) {
            if (array_key_exists($name, $matches)) {
                $parameters[$name] = $matches[$name];
            }
        }

        return true;
    }

    /** @return callable(Request, array<string, string>): Response */
    public function getHandler(): callable
    {
        return $this->handler;
    }

    /** @return MiddlewareInterface[] */
    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    private function compilePathToRegex(string $path): string
    {
        $regex = preg_replace_callback('/\{([a-zA-Z_][a-zA-Z0-9_-]*)\}/', function ($matches) {
            $this->parameterNames[] = $matches[1];
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, str_replace('/', '\/', $path));

        return '#^' . $regex . '$#';
    }
}
