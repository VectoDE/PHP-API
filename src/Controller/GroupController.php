<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\GroupService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class GroupController
{
    public function __construct(private readonly GroupService $groupService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'data' => $this->groupService->list(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->groupService->find($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('name', 2, 150)
            ->optionalString('description', 0, 500);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $group = $this->groupService->create($payload);

        return JsonResponse::created([
            'message' => 'Group created successfully.',
            'data' => $group,
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
            ->optionalString('name', 2, 150)
            ->optionalString('description', 0, 500);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $group = $this->groupService->update($id, $payload);

        return JsonResponse::fromArray([
            'message' => 'Group updated successfully.',
            'data' => $group,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        $this->groupService->delete($id);

        return JsonResponse::noContent();
    }

    public function addMember(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireInt('user_id');

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $group = $this->groupService->addMember($id, $payload['user_id']);

        return JsonResponse::fromArray([
            'message' => 'Member added successfully.',
            'data' => $group,
        ]);
    }

    public function removeMember(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $groupId = (int)($params['id'] ?? 0);
        $userId = (int)($params['userId'] ?? 0);

        $this->groupService->removeMember($groupId, $userId);

        return JsonResponse::fromArray([
            'message' => 'Member removed successfully.',
        ]);
    }

    public function members(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $groupId = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->groupService->members($groupId),
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
}
