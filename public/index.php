<?php

declare(strict_types=1);

use App\Application;

require_once dirname(__DIR__) . '/vendor/autoload.php';

spl_autoload_register(static function (string $class): void {
    if (str_starts_with($class, 'App\\')) {
        $relativePath = str_replace('\\', '/', substr($class, 4));
        $file = dirname(__DIR__) . '/src/' . $relativePath . '.php';
        if (is_file($file)) {
            require_once $file;
        }
    }
});

$app = new Application(dirname(__DIR__));
$app->run();
