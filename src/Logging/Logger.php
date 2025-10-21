<?php

declare(strict_types=1);

namespace App\Logging;

final class Logger
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        $payload = $context === [] ? '' : ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $entry = sprintf('[%s] %s: %s%s%s',
            (new \DateTimeImmutable())->format(DATE_ATOM),
            $level,
            $message,
            $payload,
            PHP_EOL
        );

        file_put_contents($this->filePath, $entry, FILE_APPEND | LOCK_EX);
    }
}
