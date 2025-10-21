<?php

declare(strict_types=1);

namespace App\Http;

final class JsonResponse extends Response
{
    /** @param array<mixed> $data */
    public static function fromArray(array $data, int $statusCode = 200): self
    {
        return new self(
            body: json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            statusCode: $statusCode,
            headers: [
                'Content-Type' => 'application/json',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
            ]
        );
    }

    /** @param array<mixed> $data */
    public static function created(array $data): self
    {
        return self::fromArray($data, 201);
    }

    public static function noContent(): self
    {
        return new self('', 204);
    }
}
