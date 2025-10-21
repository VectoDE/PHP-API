<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\PermissionService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class PermissionController
{
    public function __construct(private readonly PermissionService $permissionService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'data' => $this->permissionService->list(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->permissionService->find($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('name', 2, 150)
            ->optionalString('description', 0, 255);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $permission = $this->permissionService->create($payload);

        return JsonResponse::created([
            'message' => 'Permission created successfully.',
            'data' => $permission,
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
            ->optionalString('description', 0, 255);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $permission = $this->permissionService->update($id, $payload);

        return JsonResponse::fromArray([
            'message' => 'Permission updated successfully.',
            'data' => $permission,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        $this->permissionService->delete($id);

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
