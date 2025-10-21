<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\GroupRepository;
use App\Repository\UserRepository;

final class GroupService
{
    public function __construct(
        private readonly GroupRepository $groupRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $groups = $this->groupRepository->all();
        $ids = array_map(static fn (array $group): int => (int)$group['id'], $groups);
        $membersMap = $this->groupRepository->getMembersForGroups($ids);

        foreach ($groups as &$group) {
            $groupId = (int)$group['id'];
            $group['members'] = $membersMap[$groupId] ?? [];
        }
        unset($group);

        return $groups;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $group = $this->groupRepository->find($id);
        if ($group === null) {
            throw new HttpException(404, 'Group not found.');
        }

        $group['members'] = $this->groupRepository->getMembers($id);

        return $group;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        if ($this->groupRepository->existsByName($data['name'])) {
            throw new HttpException(409, 'Group name already in use.');
        }

        $group = $this->groupRepository->create($data);

        return $this->find((int)$group['id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (isset($data['name']) && $this->groupRepository->existsByName($data['name'], $id)) {
            throw new HttpException(409, 'Group name already in use.');
        }

        $group = $this->groupRepository->update($id, $data);
        if ($group === null) {
            throw new HttpException(404, 'Group not found.');
        }

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $deleted = $this->groupRepository->delete($id);
        if (!$deleted) {
            throw new HttpException(404, 'Group not found.');
        }
    }

    public function addMember(int $groupId, int $userId): array
    {
        $this->assertGroupExists($groupId);
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->groupRepository->addUser($groupId, $userId);

        return $this->find($groupId);
    }

    public function removeMember(int $groupId, int $userId): void
    {
        $this->assertGroupExists($groupId);
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->groupRepository->removeUser($groupId, $userId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function members(int $groupId): array
    {
        $this->assertGroupExists($groupId);

        return $this->groupRepository->getMembers($groupId);
    }

    /**
     * @return array<string, mixed>
     */
    private function assertGroupExists(int $groupId): array
    {
        $group = $this->groupRepository->find($groupId);
        if ($group === null) {
            throw new HttpException(404, 'Group not found.');
        }

        return $group;
    }
}
