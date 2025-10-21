<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class RoleRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->connection->query('SELECT id, name, description, created_at, updated_at FROM roles ORDER BY name ASC');

        return $statement !== false ? $statement->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, description, created_at, updated_at FROM roles WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByName(string $name): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, description, created_at, updated_at FROM roles WHERE name = :name LIMIT 1');
        $statement->bindValue(':name', $name);
        $statement->execute();

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(1) FROM roles WHERE name = :name';
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
        }

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':name', $name);
        if ($ignoreId !== null) {
            $statement->bindValue(':id', $ignoreId, PDO::PARAM_INT);
        }

        $statement->execute();

        return (bool)$statement->fetchColumn();
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = $this->timestamp();
        $statement = $this->connection->prepare('INSERT INTO roles (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
        $statement->execute([
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':created_at' => $timestamp,
            ':updated_at' => $timestamp,
        ]);

        $id = (int)$this->connection->lastInsertId();

        return $this->find($id) ?? [];
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>|null
     */
    public function update(int $id, array $data): ?array
    {
        $fields = [];
        $parameters = [':id' => $id];

        if (array_key_exists('name', $data)) {
            $fields[] = 'name = :name';
            $parameters[':name'] = $data['name'];
        }

        if (array_key_exists('description', $data)) {
            $fields[] = 'description = :description';
            $parameters[':description'] = $data['description'];
        }

        if ($fields === []) {
            return $this->find($id);
        }

        $fields[] = 'updated_at = :updated_at';
        $parameters[':updated_at'] = $this->timestamp();

        $sql = 'UPDATE roles SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM roles WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    /**
     * @param array<int> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->connection->beginTransaction();

        try {
            $delete = $this->connection->prepare('DELETE FROM role_permission WHERE role_id = :role_id');
            $delete->execute([':role_id' => $roleId]);

            if ($permissionIds !== []) {
                $insert = $this->connection->prepare('INSERT INTO role_permission (role_id, permission_id, created_at) VALUES (:role_id, :permission_id, :created_at)');
                $timestamp = $this->timestamp();

                foreach ($permissionIds as $permissionId) {
                    $insert->execute([
                        ':role_id' => $roleId,
                        ':permission_id' => $permissionId,
                        ':created_at' => $timestamp,
                    ]);
                }
            }

            $this->connection->commit();
        } catch (PDOException $exception) {
            $this->connection->rollBack();
            throw $exception;
        }
    }

    public function assignRoleToUser(int $roleId, int $userId): void
    {
        $statement = $this->connection->prepare('INSERT INTO user_role (user_id, role_id, created_at) VALUES (:user_id, :role_id, :created_at)');

        try {
            $statement->execute([
                ':user_id' => $userId,
                ':role_id' => $roleId,
                ':created_at' => $this->timestamp(),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
        }
    }

    public function revokeRoleFromUser(int $roleId, int $userId): void
    {
        $statement = $this->connection->prepare('DELETE FROM user_role WHERE user_id = :user_id AND role_id = :role_id');
        $statement->execute([
            ':user_id' => $userId,
            ':role_id' => $roleId,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPermissionsForRole(int $roleId): array
    {
        $statement = $this->connection->prepare('SELECT p.id, p.name, p.description FROM role_permission rp INNER JOIN permissions p ON p.id = rp.permission_id WHERE rp.role_id = :role_id ORDER BY p.name ASC');
        $statement->execute([':role_id' => $roleId]);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int> $roleIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getPermissionsForRoles(array $roleIds): array
    {
        if ($roleIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($roleIds), '?'));
        $sql = <<<SQL
            SELECT rp.role_id, p.id, p.name, p.description
            FROM role_permission rp
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE rp.role_id IN ($placeholders)
            ORDER BY p.name ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($roleIds);

        $map = [];
        while ($row = $statement->fetch()) {
            $roleId = (int)$row['role_id'];
            unset($row['role_id']);
            $map[$roleId][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        return $map;
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
