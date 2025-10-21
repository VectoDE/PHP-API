<?php

declare(strict_types=1);

namespace App\Logging;

final class LoggerFactory
{
    public static function create(string $basePath): Logger
    {
        $logDirectory = $basePath . '/storage/logs';
        if (!is_dir($logDirectory) && !mkdir($logDirectory, 0775, true) && !is_dir($logDirectory)) {
            throw new \RuntimeException(sprintf('Unable to create log directory at %s', $logDirectory));
        }

        return new Logger($logDirectory . '/app.log');
    }
}
