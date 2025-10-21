<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\ProjectRepository;
use App\Repository\TaskRepository;
use App\Repository\UserRepository;

final class TaskService
{
    /** @var string[] */
    private const STATUSES = ['todo', 'in_progress', 'in_review', 'blocked', 'done'];

    /** @var string[] */
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly UserRepository $userRepository
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listForProject(int $projectId): array
    {
        $this->assertProjectExists($projectId);

        return $this->taskRepository->allForProject($projectId);
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $taskId): array
    {
        $task = $this->taskRepository->find($taskId);
        if ($task === null) {
            throw new HttpException(404, 'Task not found.');
        }

        return $task;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(int $projectId, array $data): array
    {
        $this->assertProjectExists($projectId);
        $this->validateStatus($data['status']);
        $this->validatePriority($data['priority']);

        if (isset($data['assignee_id'])) {
            $this->assertAssigneeExists((int)$data['assignee_id']);
        }

        $payload = $data;
        $payload['project_id'] = $projectId;
        $payload['completed_at'] = $this->resolveCompletedAt($data['status']);

        $task = $this->taskRepository->create($payload);

        $this->projectRepository->addTimelineEvent($projectId, [
            'event_type' => 'task_update',
            'description' => sprintf('Task "%s" created with status %s.', $task['title'], $task['status']),
            'occurred_at' => $this->now(),
        ]);

        return $task;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $taskId, array $data): array
    {
        $task = $this->taskRepository->find($taskId);
        if ($task === null) {
            throw new HttpException(404, 'Task not found.');
        }

        if (isset($data['status'])) {
            $this->validateStatus($data['status']);
        }

        if (isset($data['priority'])) {
            $this->validatePriority($data['priority']);
        }

        if (array_key_exists('assignee_id', $data) && $data['assignee_id'] !== null) {
            $this->assertAssigneeExists((int)$data['assignee_id']);
        }

        if (isset($data['status'])) {
            $data['completed_at'] = $this->resolveCompletedAt($data['status']);
        }

        $updated = $this->taskRepository->update($taskId, $data);
        if ($updated === null) {
            throw new HttpException(404, 'Task not found.');
        }

        $this->projectRepository->addTimelineEvent((int)$task['project_id'], [
            'event_type' => 'task_update',
            'description' => sprintf('Task "%s" updated: status %s.', $updated['title'], $updated['status']),
            'occurred_at' => $this->now(),
        ]);

        return $updated;
    }

    public function delete(int $taskId): void
    {
        $task = $this->taskRepository->find($taskId);
        if ($task === null) {
            throw new HttpException(404, 'Task not found.');
        }

        $deleted = $this->taskRepository->delete($taskId);
        if (!$deleted) {
            throw new HttpException(404, 'Task not found.');
        }

        $this->projectRepository->addTimelineEvent((int)$task['project_id'], [
            'event_type' => 'task_update',
            'description' => sprintf('Task "%s" deleted.', $task['title']),
            'occurred_at' => $this->now(),
        ]);
    }

    private function validateStatus(string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            throw new HttpException(422, sprintf('Task status [%s] is not supported.', $status));
        }
    }

    private function validatePriority(string $priority): void
    {
        if (!in_array($priority, self::PRIORITIES, true)) {
            throw new HttpException(422, sprintf('Task priority [%s] is not supported.', $priority));
        }
    }

    private function resolveCompletedAt(string $status): ?string
    {
        if ($status !== 'done') {
            return null;
        }

        return $this->now();
    }

    private function assertProjectExists(int $projectId): void
    {
        if ($this->projectRepository->find($projectId) === null) {
            throw new HttpException(404, 'Project not found.');
        }
    }

    private function assertAssigneeExists(int $assigneeId): void
    {
        if ($this->userRepository->find($assigneeId) === null) {
            throw new HttpException(404, 'Assignee not found.');
        }
    }

    private function now(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }
}
