<?php

declare(strict_types=1);

// Smoke test: Composer autoload, project autoloader and JwtManager round trip.
// Runs without a test framework; exits non-zero on the first failed check.

require dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $file = dirname(__DIR__) . '/src/' . str_replace('\\', '/', substr($class, 4)) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
});

use App\Security\Jwt\JwtManager;

function check(bool $condition, string $message): void
{
    if (!$condition) {
        fwrite(STDERR, "FAIL: {$message}\n");
        exit(1);
    }
    echo "ok: {$message}\n";
}

// 48 bytes, long enough for HS256 with firebase/php-jwt 6 and 7. Test value only.
$manager = new JwtManager(str_repeat('t', 48), 3600, 'smoke-test');

$token = $manager->issueToken(['sub' => 42]);
$claims = $manager->validateToken($token);

check((int) $claims['sub'] === 42, 'token round trip keeps claims');
check($claims['iss'] === 'smoke-test', 'issuer claim is set');
check($claims['exp'] - $claims['iat'] === 3600, 'ttl is applied');

$rejected = false;
try {
    $manager->validateToken($token . 'x');
} catch (Throwable) {
    $rejected = true;
}
check($rejected, 'tampered token is rejected');
