<?php

declare(strict_types=1);

namespace App\Security\Jwt;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use stdClass;

final class JwtManager
{
    /** firebase/php-jwt >= 7 rejects HS256 keys shorter than 256 bit (32 bytes). */
    private const MIN_SECRET_BYTES = 32;

    public function __construct(
        private readonly string $secret,
        private readonly int $ttl,
        private readonly string $issuer
    ) {
        if (strlen($secret) < self::MIN_SECRET_BYTES) {
            throw new InvalidJwtSecretException(sprintf(
                'JWT_SECRET must be at least %d bytes long for HS256.',
                self::MIN_SECRET_BYTES
            ));
        }
    }

    /**
     * @param array<string, mixed> $claims
     */
    public function issueToken(array $claims): string
    {
        $issuedAt = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'iat' => $issuedAt,
            'exp' => $issuedAt + $this->ttl,
        ]);

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    /**
     * @return array<string, mixed>
     */
    public function validateToken(string $token): array
    {
        $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));

        if ($decoded instanceof stdClass) {
            return (array)$decoded;
        }

        return $decoded;
    }

    public function getTtl(): int
    {
        return $this->ttl;
    }
}
