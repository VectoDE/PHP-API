<?php

declare(strict_types=1);

namespace App\Validation;

use App\Http\Exceptions\HttpException;

final class ValidationException extends HttpException
{
    /** @param array<string, string> $errors */
    public function __construct(array $errors)
    {
        parent::__construct(422, 'Validation failed', ['errors' => $errors]);
    }
}
