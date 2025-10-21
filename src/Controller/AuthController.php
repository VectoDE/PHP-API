<?php

declare(strict_types=1);

namespace App\Controller;

use App\Http\JsonResponse;
use App\Http\Request;
use App\Service\AuthService;
use App\Validation\ValidationException;
use App\Validation\Validator;
use JsonException;

final class AuthController
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(Request $request): JsonResponse
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

        $user = $this->authService->register($payload);

        return JsonResponse::created([
            'message' => 'User registered successfully.',
            'user' => $user,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $payload = $this->extractJson($request);

        $validator = Validator::make($payload)
            ->requireString('identifier', 3)
            ->requireString('password', 8);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }

        $result = $this->authService->login($payload['identifier'], $payload['password']);

        return JsonResponse::fromArray($result);
    }

    public function logout(Request $request): JsonResponse
    {
        return JsonResponse::fromArray([
            'message' => 'Token invalidated on client side. Please discard the bearer token.',
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
