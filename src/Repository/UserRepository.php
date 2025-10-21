<?php

declare(strict_types=1);

namespace App\Repository;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        $statement = $this->connection->query('SELECT id, fullname, username, email, created_at, updated_at FROM users ORDER BY id ASC');

        return $statement !== false ? $statement->fetchAll() : [];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allWithRelations(): array
    {
        $users = $this->all();
        $ids = array_map(static fn (array $user): int => (int)$user['id'], $users);

        $rolesMap = $this->getRolesForUsers($ids);
        $permissionsMap = $this->getPermissionsForUsers($ids);
        $groupsMap = $this->getGroupsForUsers($ids);

        foreach ($users as &$user) {
            $userId = (int)$user['id'];
            $user['roles'] = $rolesMap[$userId] ?? [];
            $user['permissions'] = $permissionsMap[$userId] ?? [];
            $user['groups'] = $groupsMap[$userId] ?? [];
        }
        unset($user);

        return $users;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function find(int $id): ?array
    {
        $statement = $this->connection->prepare('SELECT id, fullname, username, email, created_at, updated_at FROM users WHERE id = :id LIMIT 1');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findWithRelations(int $id): ?array
    {
        $user = $this->find($id);
        if ($user === null) {
            return null;
        }

        $userId = (int)$user['id'];
        $user['roles'] = $this->getRolesForUsers([$userId])[$userId] ?? [];
        $user['permissions'] = $this->getPermissionsForUsers([$userId])[$userId] ?? [];
        $user['groups'] = $this->getGroupsForUsers([$userId])[$userId] ?? [];

        return $user;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array
    {
        $statement = $this->connection->prepare('SELECT id, fullname, username, email, created_at, updated_at FROM users WHERE email = :email LIMIT 1');
        $statement->bindValue(':email', $email);
        $statement->execute();
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findByUsername(string $username): ?array
    {
        $statement = $this->connection->prepare('SELECT id, fullname, username, email, created_at, updated_at FROM users WHERE username = :username LIMIT 1');
        $statement->bindValue(':username', $username);
        $statement->execute();
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findForAuth(string $identifier): ?array
    {
        $statement = $this->connection->prepare('SELECT id, fullname, username, email, password FROM users WHERE email = :identifier OR username = :identifier LIMIT 1');
        $statement->bindValue(':identifier', $identifier);
        $statement->execute();
        $result = $statement->fetch();

        return $result !== false ? $result : null;
    }

    /**
     * @return array<string, array<int, array<string, mixed>>>
     */
    public function getAccessContext(int $userId): array
    {
        $roles = $this->getRolesForUsers([$userId]);
        $permissions = $this->getPermissionsForUsers([$userId]);
        $groups = $this->getGroupsForUsers([$userId]);

        return [
            'roles' => $roles[$userId] ?? [],
            'permissions' => $permissions[$userId] ?? [],
            'groups' => $groups[$userId] ?? [],
        ];
    }

    public function existsByEmail(string $email, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(1) FROM users WHERE email = :email';
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
        }

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':email', $email);
        if ($ignoreId !== null) {
            $statement->bindValue(':id', $ignoreId, PDO::PARAM_INT);
        }

        $statement->execute();

        return (bool)$statement->fetchColumn();
    }

    public function existsByUsername(string $username, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(1) FROM users WHERE username = :username';
        if ($ignoreId !== null) {
            $sql .= ' AND id != :id';
        }

        $statement = $this->connection->prepare($sql);
        $statement->bindValue(':username', $username);
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
        $statement = $this->connection->prepare('INSERT INTO users (fullname, username, email, password, created_at, updated_at) VALUES (:fullname, :username, :email, :password, :created_at, :updated_at)');
        $now = $this->timestamp();
        $statement->execute([
            ':fullname' => $data['fullname'],
            ':username' => $data['username'],
            ':email' => $data['email'],
            ':password' => $data['password'],
            ':created_at' => $now,
            ':updated_at' => $now,
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

        if (array_key_exists('fullname', $data)) {
            $fields[] = 'fullname = :fullname';
            $parameters[':fullname'] = $data['fullname'];
        }

        if (array_key_exists('username', $data)) {
            $fields[] = 'username = :username';
            $parameters[':username'] = $data['username'];
        }

        if (array_key_exists('email', $data)) {
            $fields[] = 'email = :email';
            $parameters[':email'] = $data['email'];
        }

        if (array_key_exists('password', $data)) {
            $fields[] = 'password = :password';
            $parameters[':password'] = $data['password'];
        }

        if ($fields === []) {
            return $this->find($id);
        }

        $fields[] = 'updated_at = :updated_at';
        $parameters[':updated_at'] = $this->timestamp();

        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id';
        $statement = $this->connection->prepare($sql);
        $statement->execute($parameters);

        return $this->find($id);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM users WHERE id = :id');
        $statement->bindValue(':id', $id, PDO::PARAM_INT);
        $statement->execute();

        return $statement->rowCount() > 0;
    }

    /**
     * @param array<int> $userIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getRolesForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $placeholders = $this->buildInClause($userIds);
        $sql = <<<SQL
            SELECT ur.user_id, r.id, r.name, r.description
            FROM user_role ur
            INNER JOIN roles r ON r.id = ur.role_id
            WHERE ur.user_id IN ($placeholders)
            ORDER BY r.name ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($userIds);

        $map = [];

        while ($row = $statement->fetch()) {
            $userId = (int)$row['user_id'];
            unset($row['user_id']);
            $map[$userId][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        return $map;
    }

    /**
     * @param array<int> $userIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getPermissionsForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $placeholders = $this->buildInClause($userIds);
        $sql = <<<SQL
            SELECT DISTINCT ur.user_id, p.id, p.name, p.description
            FROM user_role ur
            INNER JOIN role_permission rp ON rp.role_id = ur.role_id
            INNER JOIN permissions p ON p.id = rp.permission_id
            WHERE ur.user_id IN ($placeholders)
            ORDER BY p.name ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($userIds);

        $map = [];

        while ($row = $statement->fetch()) {
            $userId = (int)$row['user_id'];
            unset($row['user_id']);
            $map[$userId][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        return $map;
    }

    /**
     * @param array<int> $userIds
     * @return array<int, array<int, array<string, mixed>>>
     */
    public function getGroupsForUsers(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $placeholders = $this->buildInClause($userIds);
        $sql = <<<SQL
            SELECT gu.user_id, g.id, g.name, g.description
            FROM group_user gu
            INNER JOIN groups g ON g.id = gu.group_id
            WHERE gu.user_id IN ($placeholders)
            ORDER BY g.name ASC
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($userIds);

        $map = [];

        while ($row = $statement->fetch()) {
            $userId = (int)$row['user_id'];
            unset($row['user_id']);
            $map[$userId][] = [
                'id' => (int)$row['id'],
                'name' => $row['name'],
                'description' => $row['description'],
            ];
        }

        return $map;
    }

    private function buildInClause(array $ids): string
    {
        return implode(', ', array_fill(0, count($ids), '?'));
    }

    private function timestamp(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
