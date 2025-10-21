<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use PDO;
use PDOException;

final class RbacTablesMigration
{
    public static function migrate(PDO $pdo): void
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS roles (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL UNIQUE,
                    description VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS permissions (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(150) NOT NULL UNIQUE,
                    description VARCHAR(255) NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS role_permission (
                    role_id BIGINT UNSIGNED NOT NULL,
                    permission_id BIGINT UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (role_id, permission_id),
                    CONSTRAINT fk_role_permission_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                    CONSTRAINT fk_role_permission_permission FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS user_role (
                    user_id BIGINT UNSIGNED NOT NULL,
                    role_id BIGINT UNSIGNED NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, role_id),
                    CONSTRAINT fk_user_role_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    CONSTRAINT fk_user_role_role FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            SQL);
        } else {
            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS roles (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS permissions (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL UNIQUE,
                    description TEXT NULL,
                    created_at TEXT NOT NULL,
                    updated_at TEXT NOT NULL
                )
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS role_permission (
                    role_id INTEGER NOT NULL,
                    permission_id INTEGER NOT NULL,
                    created_at TEXT NOT NULL,
                    PRIMARY KEY (role_id, permission_id),
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
                    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
                )
            SQL);

            $pdo->exec(<<<SQL
                CREATE TABLE IF NOT EXISTS user_role (
                    user_id INTEGER NOT NULL,
                    role_id INTEGER NOT NULL,
                    created_at TEXT NOT NULL,
                    PRIMARY KEY (user_id, role_id),
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
                )
            SQL);
        }

        self::seed($pdo);
    }

    private static function seed(PDO $pdo): void
    {
        $roles = [
            ['name' => 'Administrator', 'description' => 'Full access to all resources.'],
            ['name' => 'Project Manager', 'description' => 'Manage projects, tasks, and teams.'],
            ['name' => 'Team Member', 'description' => 'Collaborate on assigned tasks and projects.'],
        ];

        $permissions = [
            ['name' => 'manage_users', 'description' => 'Create and manage user accounts.'],
            ['name' => 'manage_roles', 'description' => 'Assign and update access roles.'],
            ['name' => 'manage_permissions', 'description' => 'Create and maintain permission policies.'],
            ['name' => 'manage_projects', 'description' => 'Create, update, and archive projects.'],
            ['name' => 'manage_tasks', 'description' => 'Create and update project tasks.'],
            ['name' => 'view_reports', 'description' => 'Access operational and progress reports.'],
            ['name' => 'collaborate', 'description' => 'Collaborate on assigned work items.'],
        ];

        foreach ($roles as $role) {
            self::ensureRole($pdo, $role['name'], $role['description']);
        }

        foreach ($permissions as $permission) {
            self::ensurePermission($pdo, $permission['name'], $permission['description']);
        }

        $rolePermissions = [
            'Administrator' => ['manage_users', 'manage_roles', 'manage_permissions', 'manage_projects', 'manage_tasks', 'view_reports', 'collaborate'],
            'Project Manager' => ['manage_projects', 'manage_tasks', 'view_reports', 'collaborate'],
            'Team Member' => ['manage_tasks', 'collaborate'],
        ];

        foreach ($rolePermissions as $roleName => $permissionNames) {
            $roleId = self::getRoleId($pdo, $roleName);
            if ($roleId === null) {
                continue;
            }

            $pdo->beginTransaction();
            try {
                $delete = $pdo->prepare('DELETE FROM role_permission WHERE role_id = :role_id');
                $delete->execute([':role_id' => $roleId]);

                $insert = $pdo->prepare('INSERT INTO role_permission (role_id, permission_id, created_at) VALUES (:role_id, :permission_id, :created_at)');

                foreach ($permissionNames as $permissionName) {
                    $permissionId = self::getPermissionId($pdo, $permissionName);
                    if ($permissionId === null) {
                        continue;
                    }

                    try {
                        $insert->execute([
                            ':role_id' => $roleId,
                            ':permission_id' => $permissionId,
                            ':created_at' => self::timestamp($pdo),
                        ]);
                    } catch (PDOException $exception) {
                        if ($exception->getCode() !== '23000') {
                            throw $exception;
                        }
                    }
                }

                $pdo->commit();
            } catch (PDOException $exception) {
                $pdo->rollBack();
                throw $exception;
            }
        }
    }

    private static function ensureRole(PDO $pdo, string $name, ?string $description): void
    {
        $statement = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);

        if ($statement->fetchColumn() !== false) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO roles (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
        $timestamp = self::timestamp($pdo);
        $insert->execute([
            ':name' => $name,
            ':description' => $description,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }

    private static function ensurePermission(PDO $pdo, string $name, ?string $description): void
    {
        $statement = $pdo->prepare('SELECT id FROM permissions WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);

        if ($statement->fetchColumn() !== false) {
            return;
        }

        $insert = $pdo->prepare('INSERT INTO permissions (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
        $timestamp = self::timestamp($pdo);
        $insert->execute([
            ':name' => $name,
            ':description' => $description,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);
    }

    private static function getRoleId(PDO $pdo, string $name): ?int
    {
        $statement = $pdo->prepare('SELECT id FROM roles WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);
        $result = $statement->fetchColumn();

        return $result === false ? null : (int)$result;
    }

    private static function getPermissionId(PDO $pdo, string $name): ?int
    {
        $statement = $pdo->prepare('SELECT id FROM permissions WHERE name = :name LIMIT 1');
        $statement->execute([':name' => $name]);
        $result = $statement->fetchColumn();

        return $result === false ? null : (int)$result;
    }

    private static function timestamp(PDO $pdo): string
    {
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        $format = $driver === 'mysql' ? 'Y-m-d H:i:s' : DATE_ATOM;

        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format($format);
    }
}
