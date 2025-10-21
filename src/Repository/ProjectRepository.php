<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;
use PDOException;

final class ProjectRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $sql = <<<SQL
            SELECT p.id, p.name, p.description, p.status, p.start_date, p.end_date, p.group_id, p.owner_id,
                   p.created_at, p.updated_at, g.name AS group_name, u.fullname AS owner_name
            FROM projects p
            LEFT JOIN groups g ON g.id = p.group_id
            INNER JOIN users u ON u.id = p.owner_id
            ORDER BY p.created_at DESC
        SQL;

        $statement = $this->connection->query($sql);

        return $statement !== false ? $statement->fetchAll() : [];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $sql = <<<SQL
            SELECT p.id, p.name, p.description, p.status, p.start_date, p.end_date, p.group_id, p.owner_id,
                   p.created_at, p.updated_at, g.name AS group_name, u.fullname AS owner_name
            FROM projects p
            LEFT JOIN groups g ON g.id = p.group_id
            INNER JOIN users u ON u.id = p.owner_id
            WHERE p.id = :id
            LIMIT 1
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

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
        $statement = $this->connection->prepare('INSERT INTO projects (group_id, owner_id, name, description, status, start_date, end_date, created_at, updated_at) VALUES (:group_id, :owner_id, :name, :description, :status, :start_date, :end_date, :created_at, :updated_at)');
        $statement->execute([
            ':group_id' => $data['group_id'],
            ':owner_id' => $data['owner_id'],
            ':name' => $data['name'],
            ':description' => $data['description'] ?? null,
            ':status' => $data['status'],
            ':start_date' => $data['start_date'] ?? null,
            ':end_date' => $data['end_date'] ?? null,
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

        foreach (['group_id', 'owner_id', 'name', 'description', 'status', 'start_date', 'end_date'] as $field) {
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

        $sql = 'UPDATE projects SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM projects WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    public function assignMember(int $projectId, int $userId, string $role): void
    {
        $statement = $this->connection->prepare('INSERT INTO project_members (project_id, user_id, role, created_at) VALUES (:project_id, :user_id, :role, :created_at)');

        try {
            $statement->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId,
                ':role' => $role,
                ':created_at' => $this->timestamp(),
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }
        }
    }

    public function removeMember(int $projectId, int $userId): void
    {
        $statement = $this->connection->prepare('DELETE FROM project_members WHERE project_id = :project_id AND user_id = :user_id');
        $statement->execute([
            ':project_id' => $projectId,
            ':user_id' => $userId,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMembers(int $projectId): array
    {
        $statement = $this->connection->prepare('SELECT u.id, u.fullname, u.email, u.username, pm.role FROM project_members pm INNER JOIN users u ON u.id = pm.user_id WHERE pm.project_id = :project_id ORDER BY u.fullname ASC');
        $statement->execute([':project_id' => $projectId]);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int> $projectIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getMembersForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $sql = <<<SQL
            SELECT pm.project_id, u.id, u.fullname, u.email, u.username, pm.role
            FROM project_members pm
            INNER JOIN users u ON u.id = pm.user_id
            WHERE pm.project_id IN ($placeholders)
            ORDER BY u.fullname ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($projectIds);

        $map = [];
        while ($row = $statement->fetch()) {
            $projectId = (int)$row['project_id'];
            unset($row['project_id']);
            $map[$projectId][] = [
                'id' => (int)$row['id'],
                'fullname' => $row['fullname'],
                'email' => $row['email'],
                'username' => $row['username'],
                'role' => $row['role'],
            ];
        }

        return $map;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function addTimelineEvent(int $projectId, array $data): array
    {
        $statement = $this->connection->prepare('INSERT INTO project_timelines (project_id, event_type, description, occurred_at, created_at) VALUES (:project_id, :event_type, :description, :occurred_at, :created_at)');
        $statement->execute([
            ':project_id' => $projectId,
            ':event_type' => $data['event_type'],
            ':description' => $data['description'] ?? null,
            ':occurred_at' => $data['occurred_at'],
            ':created_at' => $this->timestamp(),
        ]);

        $id = (int)$this->connection->lastInsertId();

        return $this->findTimelineEvent($id) ?? [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getTimeline(int $projectId): array
    {
        $statement = $this->connection->prepare('SELECT id, project_id, event_type, description, occurred_at, created_at FROM project_timelines WHERE project_id = :project_id ORDER BY occurred_at DESC');
        $statement->execute([':project_id' => $projectId]);

        return $statement->fetchAll() ?: [];
    }

    /**
     * @param array<int> $projectIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getTimelineForProjects(array $projectIds): array
    {
        if ($projectIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($projectIds), '?'));
        $sql = <<<SQL
            SELECT id, project_id, event_type, description, occurred_at, created_at
            FROM project_timelines
            WHERE project_id IN ($placeholders)
            ORDER BY occurred_at DESC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($projectIds);

        $map = [];
        while ($row = $statement->fetch()) {
            $projectId = (int)$row['project_id'];
            unset($row['project_id']);
            $map[$projectId][] = [
                'id' => (int)$row['id'],
                'event_type' => $row['event_type'],
                'description' => $row['description'],
                'occurred_at' => $row['occurred_at'],
                'created_at' => $row['created_at'],
            ];
        }

        return $map;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function findTimelineEvent(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, project_id, event_type, description, occurred_at, created_at FROM project_timelines WHERE id = :id LIMIT 1');
        $statement->execute([':id' => $id]);
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
