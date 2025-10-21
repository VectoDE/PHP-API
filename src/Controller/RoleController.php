<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\RoleService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class RoleController
{
    public function __construct(private readonly RoleService $roleService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'data' => $this->roleService->list(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->roleService->find($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('name', 2, 100)
            ->optionalString('description', 0, 255);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $role = $this->roleService->create($payload);

        return JsonResponse::created([
            'message' => 'Role created successfully.',
            'data' => $role,
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
            ->optionalString('name', 2, 100)
            ->optionalString('description', 0, 255);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $role = $this->roleService->update($id, $payload);

        return JsonResponse::fromArray([
            'message' => 'Role updated successfully.',
            'data' => $role,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        $this->roleService->delete($id);

        return JsonResponse::noContent();
    }

    public function syncPermissions(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireArray('permission_ids')
            ->arrayOfIntegers('permission_ids');

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $role = $this->roleService->syncPermissions($id, $payload['permission_ids']);

        return JsonResponse::fromArray([
            'message' => 'Permissions synchronized successfully.',
            'data' => $role,
        ]);
    }

    public function assign(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireInt('user_id');

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $role = $this->roleService->assignToUser($id, $payload['user_id']);

        return JsonResponse::fromArray([
            'message' => 'Role assigned to user successfully.',
            'data' => $role,
        ]);
    }

    public function revoke(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireInt('user_id');

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $this->roleService->revokeFromUser($id, $payload['user_id']);

        return JsonResponse::fromArray([
            'message' => 'Role revoked from user successfully.',
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
