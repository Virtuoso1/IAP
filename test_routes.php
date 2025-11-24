<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

$routes = app('router')->getRoutes();

echo "Total routes: " . count($routes) . PHP_EOL;

foreach ($routes as $route) {
    echo $route->uri() . PHP_EOL;
}