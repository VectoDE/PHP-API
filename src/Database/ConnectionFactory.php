<?php

declare(strict_types=1);

namespace App\Database;

use App\Config\Config;
use PDO;
use PDOException;
use RuntimeException;

final class ConnectionFactory
{
    public static function make(Config $config): PDO
    {
        try {
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_STRINGIFY_FETCHES => false,
            ];

            if ($config->get('database.driver') === 'mysql') {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $config->get('database.charset', 'utf8mb4');
                $options[PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;
            }

            $pdo = new PDO(
                $config->get('database.dsn'),
                $config->get('database.username'),
                $config->get('database.password'),
                $options
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Unable to establish database connection: ' . $exception->getMessage(), 0, $exception);
        }

        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $pdo->exec('PRAGMA foreign_keys = ON');
        }

        if ($driver === 'mysql') {
            $timezone = $config->get('database.timezone', '+00:00');
            $pdo->exec("SET time_zone = '" . $timezone . "'");
        }

        return $pdo;
    }
}
