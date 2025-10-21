<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\TaskService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class TaskController
{
    /** @var string[] */
    private const STATUSES = ['todo', 'in_progress', 'in_review', 'blocked', 'done'];

    /** @var string[] */
    private const PRIORITIES = ['low', 'medium', 'high', 'critical'];

    public function __construct(private readonly TaskService $taskService)
    {
    }

    public function list(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['projectId'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->taskService->listForProject($projectId),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $taskId = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->taskService->find($taskId),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['projectId'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('title', 3, 255)
            ->optionalString('description', 0, 2000)
            ->requireString('status', 2, 50)
            ->requireString('priority', 2, 50)
            ->optionalInt('assignee_id')
            ->optionalDate('start_date')
            ->optionalDate('due_date')
            ->in('status', self::STATUSES)
            ->in('priority', self::PRIORITIES);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if (array_key_exists('assignee_id', $payload)) {
            $payload['assignee_id'] = $payload['assignee_id'] !== null ? (int)$payload['assignee_id'] : null;
        }

        $task = $this->taskService->create($projectId, $payload);

        return JsonResponse::created([
            'message' => 'Task created successfully.',
            'data' => $task,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $taskId = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        if ($payload === []) {
            throw new ValidationException(['payload' => 'At least one attribute must be provided.']);
        }

        $validator = Validator::make($payload)
            ->optionalString('title', 3, 255)
            ->optionalString('description', 0, 2000)
            ->optionalString('status', 2, 50)
            ->optionalString('priority', 2, 50)
            ->optionalInt('assignee_id')
            ->optionalDate('start_date')
            ->optionalDate('due_date')
            ->optionalIn('status', self::STATUSES)
            ->optionalIn('priority', self::PRIORITIES);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if (array_key_exists('assignee_id', $payload)) {
            $payload['assignee_id'] = $payload['assignee_id'] !== null ? (int)$payload['assignee_id'] : null;
        }

        $task = $this->taskService->update($taskId, $payload);

        return JsonResponse::fromArray([
            'message' => 'Task updated successfully.',
            'data' => $task,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $taskId = (int)($params['id'] ?? 0);

        $this->taskService->delete($taskId);

        return JsonResponse::noContent();
    }

    /**
     * @return array<string, mixed>
     */
    private function extractJson(Request $request): array
    {
        try {
            return $request->getJsonPayload();
        } catch (JsonException $exception) {
            throw new ValidationException(['payload' => 'Invalid JSON payload: ' . $exception->getMessage()]);
        }
    }
}
