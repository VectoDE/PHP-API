<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Http\Exceptions\HttpException;
use App\Http\Request;
use App\Http\Response;
use App\Security\Jwt\JwtManager;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly JwtManager $jwtManager)
    {
    }

    public function process(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if ($token === null) {
            throw new HttpException(401, 'Authorization header missing.');
        }

        try {
            $claims = $this->jwtManager->validateToken($token);
        } catch (Throwable $exception) {
            throw new HttpException(401, 'Invalid or expired authentication token.', previous: $exception);
        }

        $request = $request->withAttribute('auth', $claims);

        return $next($request);
    }
}
