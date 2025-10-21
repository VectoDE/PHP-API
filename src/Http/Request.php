<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;

final class Request
{
    private array $jsonPayload;
    private array $attributes = [];

    private function __construct(
        private readonly string $method,
        private readonly string $path,
        private readonly array $query,
        private readonly array $headers,
        private readonly ?string $rawBody
    ) {
        $this->jsonPayload = [];
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $body = file_get_contents('php://input') ?: null;

        return new self($method, $path, $_GET, array_change_key_case($headers, CASE_LOWER), $body);
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getQueryParams(): array
    {
        return $this->query;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower($name);
        return $this->headers[$normalized] ?? $default;
    }

    public function getJsonPayload(): array
    {
        if ($this->jsonPayload !== []) {
            return $this->jsonPayload;
        }

        if ($this->rawBody === null || $this->rawBody === '') {
            $this->jsonPayload = [];
            return $this->jsonPayload;
        }

        try {
            $decoded = json_decode($this->rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new JsonException('Invalid JSON payload: ' . $exception->getMessage(), previous: $exception);
        }

        $this->jsonPayload = is_array($decoded) ? $decoded : [];

        return $this->jsonPayload;
    }

    public function hasJsonPayload(): bool
    {
        return $this->rawBody !== null && $this->rawBody !== '';
    }

    public function bearerToken(): ?string
    {
        $header = $this->getHeader('Authorization');
        if ($header === null) {
            return null;
        }

        if (stripos($header, 'Bearer ') !== 0) {
            return null;
        }

        return trim(substr($header, 7));
    }

    public function withAttribute(string $key, mixed $value): self
    {
        $clone = clone $this;
        $clone->attributes[$key] = $value;

        return $clone;
    }

    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }
}
