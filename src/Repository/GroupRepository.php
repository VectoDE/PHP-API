<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class GroupRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->connection->query('SELECT id, name, description, created_at, updated_at FROM groups ORDER BY name ASC');

        return $statement !== false ? $statement->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, description, created_at, updated_at FROM groups WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(1) FROM groups WHERE name = :name';
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
        $statement = $this->connection->prepare('INSERT INTO groups (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
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

        $sql = 'UPDATE groups SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM groups WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function addUser(int $groupId, int $userId): void
    {
        $statement = $this->connection->prepare('INSERT INTO group_user (group_id, user_id, created_at) VALUES (:group_id, :user_id, :created_at)');

        try {
            $statement->execute([
                ':group_id' => $groupId,
                ':user_id' => $userId,
                ':created_at' => $this->timestamp(),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
        }
    }

    public function removeUser(int $groupId, int $userId): void
    {
        $statement = $this->connection->prepare('DELETE FROM group_user WHERE group_id = :group_id AND user_id = :user_id');
        $statement->execute([
            ':group_id' => $groupId,
            ':user_id' => $userId,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMembers(int $groupId): array
    {
        $statement = $this->connection->prepare('SELECT u.id, u.fullname, u.email, u.username FROM group_user gu INNER JOIN users u ON u.id = gu.user_id WHERE gu.group_id = :group_id ORDER BY u.fullname ASC');
        $statement->execute([':group_id' => $groupId]);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int> $groupIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getMembersForGroups(array $groupIds): array
    {
        if ($groupIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($groupIds), '?'));
        $sql = <<<SQL
            SELECT gu.group_id, u.id, u.fullname, u.email, u.username
            FROM group_user gu
            INNER JOIN users u ON u.id = gu.user_id
            WHERE gu.group_id IN ($placeholders)
            ORDER BY u.fullname ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($groupIds);

        $map = [];
        while ($row = $statement->fetch()) {
            $groupId = (int)$row['group_id'];
            unset($row['group_id']);
            $map[$groupId][] = [
                'id' => (int)$row['id'],
                'fullname' => $row['fullname'],
                'email' => $row['email'],
                'username' => $row['username'],
            ];
        }

        return $map;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getGroupsForUser(int $userId): array
    {
        $statement = $this->connection->prepare('SELECT g.id, g.name, g.description FROM group_user gu INNER JOIN groups g ON g.id = gu.group_id WHERE gu.user_id = :user_id ORDER BY g.name ASC');
        $statement->execute([':user_id' => $userId]);

        return $statement->fetchAll() ?: [];
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
