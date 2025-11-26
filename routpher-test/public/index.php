<?php
/**
 * ROUTPHER Framework - Front Controller
 * All requests are routed through this file
 */

// Load autoloader and bootstrap
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\App;

// Create application instance
$app = new App(__DIR__ . '/..');

// Register global middleware
$app->use([\App\Middleware\SecurityHeaders::class, 'handle']);

if (env('CSRF_ENABLED', true)) {
    $app->use([\App\Core\CSRF::class, 'verify']);
}

$app->use([\App\Core\Auth::class, 'loadUser']);

// Run the application
$app->run();
