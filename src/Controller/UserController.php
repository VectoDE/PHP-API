<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\UserService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class UserController
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'data' => $this->userService->list(),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);

        return JsonResponse::fromArray([
            'data' => $this->userService->find($id),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('fullname', 3, 255)
            ->requireString('username', 3, 100)
            ->email('email')
            ->requireString('password', 8, 255);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $user = $this->userService->create($payload);

        return JsonResponse::created([
            'message' => 'User created successfully.',
            'data' => $user,
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
            ->optionalString('fullname', 3, 255)
            ->optionalString('username', 3, 100)
            ->optionalString('password', 8, 255);

        $errors = $validator->fails() ? $validator->errors() : [];

        if (array_key_exists('email', $payload)) {
            $emailValidator = Validator::make(['email' => $payload['email']]);
            $emailValidator->email('email');
            if ($emailValidator->fails()) {
                $errors = array_merge($errors, $emailValidator->errors());
            }
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        $user = $this->userService->update($id, $payload);

        return JsonResponse::fromArray([
            'message' => 'User updated successfully.',
            'data' => $user,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $id = (int)($params['id'] ?? 0);
        $this->userService->delete($id);

        return JsonResponse::noContent();
    }

    public function findByEmail(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $email = (string)($params['email'] ?? '');

        return JsonResponse::fromArray([
            'data' => $this->userService->findByEmail($email),
        ]);
    }

    public function findByUsername(Request $request): JsonResponse
    {
        $params = $request->getAttribute('routeParameters', []);
        $username = (string)($params['username'] ?? '');

        return JsonResponse::fromArray([
            'data' => $this->userService->findByUsername($username),
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
