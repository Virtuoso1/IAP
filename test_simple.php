<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

// Check if the route files exist
echo "Checking if route files exist..." . PHP_EOL;
echo "web.php exists: " . (file_exists(__DIR__ . '/routes/web.php') ? 'YES' : 'NO') . PHP_EOL;
echo "api.php exists: " . (file_exists(__DIR__ . '/routes/api.php') ? 'YES' : 'NO') . PHP_EOL;
echo "api_moderation.php exists: " . (file_exists(__DIR__ . '/routes/api_moderation.php') ? 'YES' : 'NO') . PHP_EOL;

// Try to manually load the routes
echo PHP_EOL . "Trying to manually load routes..." . PHP_EOL;

try {
    $router = app('router');
    
    // Load web routes
    if (file_exists(__DIR__ . '/routes/web.php')) {
        require __DIR__ . '/routes/web.php';
        echo "Web routes loaded successfully" . PHP_EOL;
    }
    
    // Load API routes
    if (file_exists(__DIR__ . '/routes/api.php')) {
        require __DIR__ . '/routes/api.php';
        echo "API routes loaded successfully" . PHP_EOL;
    }
    
    $routes = $router->getRoutes();
    echo "Total routes after manual loading: " . count($routes) . PHP_EOL;
    
    foreach ($routes as $route) {
        echo $route->uri() . PHP_EOL;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}