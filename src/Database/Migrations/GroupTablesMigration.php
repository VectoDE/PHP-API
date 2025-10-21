<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use PDO;
use PDOException;

final class GroupTablesMigration
{
    public static function migrate(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS groups (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL UNIQUE,
                    description TEXT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS group_user (
                    group_id BIGINT UNSIGNED NOT NULL,
                    user_id BIGINT UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (group_id, user_id),
                    CONSTRAINT fk_group_user_group FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                    CONSTRAINT fk_group_user_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        } else {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS groups (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS group_user (
                    group_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    created_at TEXT NOT NULL,
                    PRIMARY KEY (group_id, user_id),
                    FOREIGN KEY (group_id) REFERENCES groups(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )
            SQL);
        }

        self::seedDefaultGroup($pdo);
    }

    private static function seedDefaultGroup(PDO $pdo): void
    {
        $statement = $pdo->prepare('SELECT id FROM groups WHERE name = :name LIMIT 1');
        $statement->execute([':name' => 'Global']);

        if ($statement->fetchColumn() !== false) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO groups (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
        $timestamp = self::timestamp($pdo);
        $insert->execute([
            ':name' => 'Global',
            ':description' => 'Default organization-wide group.',
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }

    private static function timestamp(PDO $pdo): string
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $format = $driver === 'mysql' ? 'Y-m-d H:i:s' : DATE_ATOM;

        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format($format);
    }
}
