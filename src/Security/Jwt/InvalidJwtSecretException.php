<?php

declare(strict_types=1);

namespace App\Security\Jwt;

use RuntimeException;

final class InvalidJwtSecretException extends RuntimeException
{
}
