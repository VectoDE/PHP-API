<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\GroupRepository;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;

final class ProjectService
{
    /** @var string[] */
    private const STATUSES = ['planned', 'active', 'on_hold', 'completed', 'archived'];

    /** @var string[] */
    private const TIMELINE_EVENT_TYPES = ['milestone', 'status_change', 'risk', 'note', 'task_update'];

    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly GroupRepository $groupRepository,
        private readonly UserRepository $userRepository,
        private readonly TaskRepository $taskRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $projects = $this->projectRepository->all();
        $ids = array_map(static fn (array $project): int => (int)$project['id'], $projects);
        $membersMap = $this->projectRepository->getMembersForProjects($ids);
        $tasksMap = $this->taskRepository->getTasksForProjects($ids);
        $timelineMap = $this->projectRepository->getTimelineForProjects($ids);

        foreach ($projects as &$project) {
            $projectId = (int)$project['id'];
            $project['members'] = $membersMap[$projectId] ?? [];
            $project['tasks'] = $tasksMap[$projectId] ?? [];
            $project['timeline'] = $timelineMap[$projectId] ?? [];
        }
        unset($project);

        return $projects;
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $project = $this->projectRepository->find($id);
        if ($project === null) {
            throw new HttpException(404, 'Project not found.');
        }

        $project['members'] = $this->projectRepository->getMembers($id);
        $project['tasks'] = $this->taskRepository->allForProject($id);
        $project['timeline'] = $this->projectRepository->getTimeline($id);

        return $project;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        $this->validateStatus($data['status']);
        $this->assertOwnerExists((int)$data['owner_id']);

        if ($data['group_id'] !== null) {
            $this->assertGroupExists((int)$data['group_id']);
        }

        $project = $this->projectRepository->create($data);

        return $this->find((int)$project['id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        if (array_key_exists('owner_id', $data)) {
            if ($data['owner_id'] === null) {
                throw new HttpException(422, 'Owner cannot be null.');
            }

            $this->assertOwnerExists((int)$data['owner_id']);
        }

        if (array_key_exists('group_id', $data) && $data['group_id'] !== null) {
            $this->assertGroupExists((int)$data['group_id']);
        }

        $project = $this->projectRepository->update($id, $data);
        if ($project === null) {
            throw new HttpException(404, 'Project not found.');
        }

        return $this->find($id);
    }

    public function delete(int $id): void
    {
        $deleted = $this->projectRepository->delete($id);
        if (!$deleted) {
            throw new HttpException(404, 'Project not found.');
        }
    }

    public function assignMember(int $projectId, int $userId, string $role): array
    {
        $this->assertProjectExists($projectId);
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->projectRepository->assignMember($projectId, $userId, $role);

        return $this->find($projectId);
    }

    public function removeMember(int $projectId, int $userId): void
    {
        $this->assertProjectExists($projectId);
        $user = $this->userRepository->find($userId);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $this->projectRepository->removeMember($projectId, $userId);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function timeline(int $projectId): array
    {
        $this->assertProjectExists($projectId);

        return $this->projectRepository->getTimeline($projectId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function addTimelineEvent(int $projectId, array $data): array
    {
        $this->assertProjectExists($projectId);
        $this->validateTimelineEventType($data['event_type']);

        return $this->projectRepository->addTimelineEvent($projectId, $data);
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException(422, sprintf('Status [%s] is not supported.', $status));
        }
    }

    private function validateTimelineEventType(string $type): void
    {
        if (!in_array($type, self::TIMELINE_EVENT_TYPES, true)) {
            throw new HttpException(422, sprintf('Timeline event type [%s] is not supported.', $type));
        }
    }

    private function assertOwnerExists(int $ownerId): void
    {
        if ($this->userRepository->find($ownerId) === null) {
            throw new HttpException(404, 'Owner not found.');
        }
    }

    private function assertGroupExists(int $groupId): void
    {
        if ($this->groupRepository->find($groupId) === null) {
            throw new HttpException(404, 'Group not found.');
        }
    }

    private function assertProjectExists(int $projectId): void
    {
        if ($this->projectRepository->find($projectId) === null) {
            throw new HttpException(404, 'Project not found.');
        }
    }
}
