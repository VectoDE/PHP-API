<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\ProjectService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use DateTimeImmutable;
use DateTimeZone;
use JsonException;

final class ProjectController
{
    /** @var string[] */
    private const STATUSES = ['planned', 'active', 'on_hold', 'completed', 'archived'];

    /** @var string[] */
    private const TIMELINE_EVENT_TYPES = ['milestone', 'status_change', 'risk', 'note', 'task_update'];

    public function __construct(private readonly ProjectService $projectService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'data' => $this->projectService->list(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->projectService->find($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('name', 3, 255)
            ->requireInt('owner_id')
            ->optionalInt('group_id')
            ->requireString('status', 2, 50)
            ->optionalString('description', 0, 2000)
            ->optionalDate('start_date')
            ->optionalDate('end_date')
            ->in('status', self::STATUSES);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $payload['owner_id'] = (int)$payload['owner_id'];
        $payload['group_id'] = array_key_exists('group_id', $payload) && $payload['group_id'] !== null
            ? (int)$payload['group_id']
            : null;

        $project = $this->projectService->create($payload);

        return JsonResponse::created([
            'message' => 'Project created successfully.',
            'data' => $project,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        if ($payload === []) {
            throw new ValidationException(['payload' => 'At least one attribute must be provided.']);
        }

        $validator = Validator::make($payload)
            ->optionalString('name', 3, 255)
            ->optionalInt('owner_id')
            ->optionalInt('group_id')
            ->optionalString('status', 2, 50)
            ->optionalString('description', 0, 2000)
            ->optionalDate('start_date')
            ->optionalDate('end_date')
            ->optionalIn('status', self::STATUSES);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if (array_key_exists('owner_id', $payload)) {
            $payload['owner_id'] = $payload['owner_id'] !== null ? (int)$payload['owner_id'] : null;
        }

        if (array_key_exists('group_id', $payload)) {
            $payload['group_id'] = $payload['group_id'] !== null ? (int)$payload['group_id'] : null;
        }

        $project = $this->projectService->update($id, $payload);

        return JsonResponse::fromArray([
            'message' => 'Project updated successfully.',
            'data' => $project,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        $this->projectService->delete($id);

        return JsonResponse::noContent();
    }

    public function addMember(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireInt('user_id')
            ->requireString('role', 2, 100);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $project = $this->projectService->assignMember($projectId, $payload['user_id'], $payload['role']);

        return JsonResponse::fromArray([
            'message' => 'Project member added successfully.',
            'data' => $project,
        ]);
    }

    public function removeMember(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['id'] ?? 0);
        $userId = (int)($params['userId'] ?? 0);

        $this->projectService->removeMember($projectId, $userId);

        return JsonResponse::fromArray([
            'message' => 'Project member removed successfully.',
        ]);
    }

    public function timeline(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->projectService->timeline($projectId),
        ]);
    }

    public function addTimelineEvent(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $projectId = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('event_type', 2, 100)
            ->optionalString('description', 0, 2000)
            ->in('event_type', self::TIMELINE_EVENT_TYPES);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        if (isset($payload['occurred_at']) && !is_string($payload['occurred_at'])) {
            throw new ValidationException(['occurred_at' => 'The occurred_at field must be a string timestamp.']);
        }

        $occurredAt = $payload['occurred_at'] ?? $this->now();
        if (isset($payload['occurred_at']) && !$this->isValidDateTime($payload['occurred_at'])) {
            throw new ValidationException(['occurred_at' => 'The occurred_at field must be a valid timestamp (Y-m-d H:i:s).']);
        }

        $event = $this->projectService->addTimelineEvent($projectId, [
            'event_type' => $payload['event_type'],
            'description' => $payload['description'] ?? null,
            'occurred_at' => $occurredAt,
        ]);

        return JsonResponse::fromArray([
            'message' => 'Timeline event recorded successfully.',
            'data' => $event,
        ]);
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

    private function now(): string
    {
        return (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i:s');
    }

    private function isValidDateTime(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $value, new DateTimeZone('UTC'));

        return $date !== false && $date->format('Y-m-d H:i:s') === $value;
    }
}
