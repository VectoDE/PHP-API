<?php

declare(strict_types=1);

namespace App\Config;

use RuntimeException;

final class Config
{
    private array $config;

    public function __construct(private readonly string $basePath)
    {
        $this->config = [
            'app' => $this->resolveAppConfig(),
            'database' => $this->resolveDatabaseConfig(),
            'jwt' => $this->resolveJwtConfig(),
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value = $this->config;

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function basePath(string $path = ''): string
    {
        return rtrim($this->basePath . '/' . ltrim($path, '/'), '/');
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveAppConfig(): array
    {
        return [
            'name' => $_ENV['APP_NAME'] ?? 'PHP Enterprise API',
            'env' => $_ENV['APP_ENV'] ?? 'production',
            'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'url' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveDatabaseConfig(): array
    {
        $driver = strtolower($_ENV['DB_DRIVER'] ?? 'mysql');

        if ($driver === 'sqlite') {
            $path = $this->resolveSqlitePath();

            return [
                'driver' => 'sqlite',
                'dsn' => 'sqlite:' . $path,
                'username' => null,
                'password' => null,
                'charset' => 'utf8',
                'timezone' => $_ENV['DB_TIMEZONE'] ?? '+00:00',
            ];
        }

        if ($driver !== 'mysql') {
            throw new RuntimeException(sprintf('Unsupported database driver [%s]. Only mysql and sqlite are supported.', $driver));
        }

        $host = $_ENV['DB_HOST'] ?? '127.0.0.1';
        $port = (int)($_ENV['DB_PORT'] ?? 3306);
        $database = $_ENV['DB_DATABASE'] ?? 'php_enterprise_api';
        $charset = $_ENV['DB_CHARSET'] ?? 'utf8mb4';

        return [
            'driver' => 'mysql',
            'host' => $host,
            'port' => $port,
            'database' => $database,
            'username' => $_ENV['DB_USERNAME'] ?? 'root',
            'password' => $_ENV['DB_PASSWORD'] ?? '',
            'charset' => $charset,
            'timezone' => $_ENV['DB_TIMEZONE'] ?? '+00:00',
            'dsn' => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $host, $port, $database, $charset),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveJwtConfig(): array
    {
        return [
            'secret' => $this->resolveJwtSecret(),
            'ttl' => (int)($_ENV['JWT_TTL'] ?? 3600),
            'issuer' => rtrim($_ENV['APP_URL'] ?? 'http://localhost', '/'),
        ];
    }

    private function resolveSqlitePath(): string
    {
        $databaseDirectory = $this->basePath . '/storage/database';
        if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
            throw new RuntimeException(sprintf('Unable to create database directory at %s', $databaseDirectory));
        }

        return $databaseDirectory . '/database.sqlite';
    }

    private function resolveJwtSecret(): string
    {
        $secret = $_ENV['JWT_SECRET'] ?? null;
        if ($secret === null || $secret === '') {
            return 'please-change-me-super-secret-key';
        }

        return $secret;
    }
}
