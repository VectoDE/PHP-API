<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class PermissionRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->connection->query('SELECT id, name, description, created_at, updated_at FROM permissions ORDER BY name ASC');

        return $statement !== false ? $statement->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, name, description, created_at, updated_at FROM permissions WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    public function existsByName(string $name, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(1) FROM permissions WHERE name = :name';
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
        $statement = $this->connection->prepare('INSERT INTO permissions (name, description, created_at, updated_at) VALUES (:name, :description, :created_at, :updated_at)');
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

        $sql = 'UPDATE permissions SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM permissions WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
