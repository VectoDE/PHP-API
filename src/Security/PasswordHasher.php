<?php

declare(strict_types=1);

namespace App\Security;

use RuntimeException;

final class PasswordHasher
{
    public function hash(string $password): string
    {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        if ($hash === false) {
            throw new RuntimeException('Unable to hash password.');
        }

        return $hash;
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
