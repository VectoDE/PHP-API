<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\RoleRepository;
use App\Repository\UserRepository;
use App\Security\Jwt\JwtManager;
use App\Security\PasswordHasher;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly PasswordHasher $passwordHasher,
        private readonly JwtManager $jwtManager
    ) {
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function register(array $data): array
    {
        if ($this->userRepository->existsByEmail($data['email'])) {
            throw new HttpException(409, 'Email already in use.');
        }

        if ($this->userRepository->existsByUsername($data['username'])) {
            throw new HttpException(409, 'Username already in use.');
        }

        $hashedPassword = $this->passwordHasher->hash($data['password']);

        $user = $this->userRepository->create([
            'fullname' => $data['fullname'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $hashedPassword,
        ]);

        $defaultRole = $this->roleRepository->findByName('Team Member');
        if ($defaultRole !== null) {
            $this->roleRepository->assignRoleToUser((int)$defaultRole['id'], (int)$user['id']);
        }

        return $this->userRepository->findWithRelations((int)$user['id']);
    }

    /**
     * @return array<string, mixed>
     */
    public function login(string $identifier, string $password): array
    {
        $user = $this->userRepository->findForAuth($identifier);
        if ($user === null || !$this->passwordHasher->verify($password, $user['password'])) {
            throw new HttpException(401, 'Invalid credentials.');
        }

        $token = $this->jwtManager->issueToken([
            'sub' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
        ]);

        $context = $this->userRepository->getAccessContext((int)$user['id']);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $this->jwtManager->getTtl(),
            'user' => [
                'id' => (int)$user['id'],
                'fullname' => $user['fullname'],
                'username' => $user['username'],
                'email' => $user['email'],
                'roles' => $context['roles'],
                'permissions' => $context['permissions'],
                'groups' => $context['groups'],
            ],
        ];
    }
}
