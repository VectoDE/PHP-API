<?php

declare(strict_types=1);

namespace App\Http\Exceptions;

use RuntimeException;

class HttpException extends RuntimeException
{
    /** @param array<mixed> $payload */
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly array $payload = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /** @return array<mixed> */
    public function getPayload(): array
    {
        return $this->payload;
    }
}
