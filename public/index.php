<?php

declare(strict_types=1);

use App\Core\App;
use App\Core\Router;

session_start();

define('BASE_PATH', dirname(__DIR__));

if (PHP_SAPI === 'cli') {
    echo json_encode(['service' => 'ordena', 'status' => 'ok']) . PHP_EOL;
    return;
}

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require BASE_PATH . '/config/helpers.php';

$app = new App(require BASE_PATH . '/config/app.php');
$router = new Router($app);

require BASE_PATH . '/routes/web.php';

$router->dispatch($_SERVER['REQUEST_METHOD'], parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
