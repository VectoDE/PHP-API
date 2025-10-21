<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\PermissionRepository;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;

final class RoleService
{
    public function __construct(
        private readonly RoleRepository $roleRepository,
        private readonly PermissionRepository $permissionRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $roles = $this->roleRepository->all();
        $roleIds = array_map(static fn (array $role): int => (int)$role['id'], $roles);
        $permissionsMap = $this->roleRepository->getPermissionsForRoles($roleIds);

        foreach ($roles as &$role) {
            $roleId = (int)$role['id'];
            $role['permissions'] = $permissionsMap[$roleId] ?? [];
        }
        unset($role);

        return $roles;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $role = $this->roleRepository->find($id);
        if ($role === null) {
            throw new HttpException(404, 'Role not found.');
        }

        $role['permissions'] = $this->roleRepository->getPermissionsForRole($id);

        return $role;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        if ($this->roleRepository->existsByName($data['name'])) {
            throw new HttpException(409, 'Role name already in use.');
        }

        $role = $this->roleRepository->create($data);

        return $this->find((int)$role['id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (isset($data['name']) && $this->roleRepository->existsByName($data['name'], $id)) {
            throw new HttpException(409, 'Role name already in use.');
        }

        $role = $this->roleRepository->update($id, $data);
        if ($role === null) {
            throw new HttpException(404, 'Role not found.');
        }

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $deleted = $this->roleRepository->delete($id);
        if (!$deleted) {
            throw new HttpException(404, 'Role not found.');
        }
    }

    /**
     * @param array<int> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): array
    {
        $this->assertRoleExists($roleId);

        foreach ($permissionIds as $permissionId) {
            if ($this->permissionRepository->find($permissionId) === null) {
                throw new HttpException(404, sprintf('Permission [%d] not found.', $permissionId));
            }
        }

        $this->roleRepository->syncPermissions($roleId, $permissionIds);

        return $this->find($roleId);
    }

    public function assignToUser(int $roleId, int $userId): array
    {
        $role = $this->assertRoleExists($roleId);
        $user = $this->userRepository->findWithRelations($userId);

        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->roleRepository->assignRoleToUser($roleId, $userId);

        return $this->find($roleId);
    }

    public function revokeFromUser(int $roleId, int $userId): void
    {
        $this->assertRoleExists($roleId);
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->roleRepository->revokeRoleFromUser($roleId, $userId);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertRoleExists(int $roleId): array
    {
        $role = $this->roleRepository->find($roleId);
        if ($role === null) {
            throw new HttpException(404, 'Role not found.');
        }

        return $role;
    }
}
