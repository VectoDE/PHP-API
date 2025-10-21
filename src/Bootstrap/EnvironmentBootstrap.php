<?php

declare(strict_types=1);

namespace App\Bootstrap;

final class EnvironmentBootstrap
{
    public static function init(string $basePath): void
    {
        self::loadEnvFile($basePath . '/.env');

        date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

        $debug = filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('display_startup_errors', $debug ? '1' : '0');

        if ($debug) {
            error_reporting(E_ALL);
        } else {
            error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
        }
    }

    private static function loadEnvFile(string $path): void
    {
        if (!is_file($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (str_starts_with($trimmed, 'export ')) {
                $trimmed = trim(substr($trimmed, 7));
            }

            $parts = explode('=', $trimmed, 2);
            if (count($parts) !== 2) {
                continue;
            }

            [$name, $value] = $parts;
            $name = trim($name);
            $value = self::sanitizeValue(trim($value));

            if ($name === '') {
                continue;
            }

            $_ENV[$name] = $value;
            putenv($name . '=' . $value);
        }
    }

    private static function sanitizeValue(string $value): string
    {
        if ($value === '') {
            return $value;
        }

        if (($value[0] === '"' && str_ends_with($value, '"')) || ($value[0] === '\'' && str_ends_with($value, '\''))) {
            $value = substr($value, 1, -1);
        }

        return $value;
    }
}
