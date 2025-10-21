<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\PermissionRepository;

final class PermissionService
{
    public function __construct(private readonly PermissionRepository $permissionRepository)
    {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->permissionRepository->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $permission = $this->permissionRepository->find($id);
        if ($permission === null) {
            throw new HttpException(404, 'Permission not found.');
        }

        return $permission;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        if ($this->permissionRepository->existsByName($data['name'])) {
            throw new HttpException(409, 'Permission name already in use.');
        }

        $permission = $this->permissionRepository->create($data);

        return $this->find((int)$permission['id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (isset($data['name']) && $this->permissionRepository->existsByName($data['name'], $id)) {
            throw new HttpException(409, 'Permission name already in use.');
        }

        $permission = $this->permissionRepository->update($id, $data);
        if ($permission === null) {
            throw new HttpException(404, 'Permission not found.');
        }

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $deleted = $this->permissionRepository->delete($id);
        if (!$deleted) {
            throw new HttpException(404, 'Permission not found.');
        }
    }
}
