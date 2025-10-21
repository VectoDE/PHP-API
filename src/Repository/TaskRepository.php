<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class TaskRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allForProject(int $projectId): array
    {
        $statement = $this->connection->prepare('SELECT id, project_id, title, description, status, priority, assignee_id, start_date, due_date, completed_at, created_at, updated_at FROM tasks WHERE project_id = :project_id ORDER BY created_at DESC');
        $statement->execute([':project_id' => $projectId]);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, project_id, title, description, status, priority, assignee_id, start_date, due_date, completed_at, created_at, updated_at FROM tasks WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $timestamp = $this->timestamp();
        $statement = $this->connection->prepare('INSERT INTO tasks (project_id, title, description, status, priority, assignee_id, start_date, due_date, completed_at, created_at, updated_at) VALUES (:project_id, :title, :description, :status, :priority, :assignee_id, :start_date, :due_date, :completed_at, :created_at, :updated_at)');
        $statement->execute([
            ':project_id' => $data['project_id'],
            ':title' => $data['title'],
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'],
            ':priority' => $data['priority'],
            ':assignee_id' => $data['assignee_id'] ?? null,
            ':start_date' => $data['start_date'] ?? null,
            ':due_date' => $data['due_date'] ?? null,
            ':completed_at' => $data['completed_at'] ?? null,
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

        foreach (['title', 'description', 'status', 'priority', 'assignee_id', 'start_date', 'due_date', 'completed_at'] as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = $field . ' = :' . $field;
                $parameters[':' . $field] = $data[$field];
            }
        }

        if ($fields === []) {
            return $this->find($id);
        }

        $fields[] = 'updated_at = :updated_at';
        $parameters[':updated_at'] = $this->timestamp();

        $sql = 'UPDATE tasks SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM tasks WHERE id = :id');
        $statement->execute([':id' => $id]);

        return $statement->rowCount() > 0;
    }

    /**
     * @param array<int> $projectIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getTasksForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $sql = <<<SQL
            SELECT id, project_id, title, description, status, priority, assignee_id, start_date, due_date, completed_at, created_at, updated_at
            FROM tasks
            WHERE project_id IN ($placeholders)
            ORDER BY created_at DESC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($projectIds);

        $map = [];
        while ($row = $statement->fetch()) {
            $projectId = (int)$row['project_id'];
            unset($row['project_id']);
            $map[$projectId][] = $row;
        }

        return $map;
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
