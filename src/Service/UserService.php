<?php

declare(strict_types=1);

namespace App\Service;

use App\Http\Exceptions\HttpException;
use App\Repository\UserRepository;
use App\Security\PasswordHasher;

final class UserService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PasswordHasher $passwordHasher
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        return $this->userRepository->allWithRelations();
    }

    /**
     * @return array<string, mixed>
     */
    public function find(int $id): array
    {
        $user = $this->userRepository->findWithRelations($id);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    public function findByEmail(string $email): array
    {
        $user = $this->userRepository->findByEmail($email);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $context = $this->userRepository->getAccessContext((int)$user['id']);

        return array_merge($user, $context);
    }

    /**
     * @return array<string, mixed>
     */
    public function findByUsername(string $username): array
    {
        $user = $this->userRepository->findByUsername($username);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        $context = $this->userRepository->getAccessContext((int)$user['id']);

        return array_merge($user, $context);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function create(array $data): array
    {
        if ($this->userRepository->existsByEmail($data['email'])) {
            throw new HttpException(409, 'Email already in use.');
        }

        if ($this->userRepository->existsByUsername($data['username'])) {
            throw new HttpException(409, 'Username already in use.');
        }

        $data['password'] = $this->passwordHasher->hash($data['password']);

        $created = $this->userRepository->create($data);

        return $this->userRepository->findWithRelations((int)$created['id']);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(int $id, array $data): array
    {
        if (isset($data['email']) && $this->userRepository->existsByEmail($data['email'], $id)) {
            throw new HttpException(409, 'Email already in use.');
        }

        if (isset($data['username']) && $this->userRepository->existsByUsername($data['username'], $id)) {
            throw new HttpException(409, 'Username already in use.');
        }

        if (isset($data['password'])) {
            $data['password'] = $this->passwordHasher->hash($data['password']);
        }

        $user = $this->userRepository->update($id, $data);
        if ($user === null) {
            throw new HttpException(404, 'User not found.');
        }

        return $this->userRepository->findWithRelations($id);
    }

    public function delete(int $id): void
    {
        $deleted = $this->userRepository->delete($id);

        if (!$deleted) {
            throw new HttpException(404, 'User not found.');
        }
    }
}
