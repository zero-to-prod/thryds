<?php

declare(strict_types=1);

/**
 * Print all registered routes as a JSON array.
 *
 * Each entry: { "name", "path", "guard", "description", "operations": [{ "method", "description" }] }
 *
 * Usage: docker compose exec web php scripts/list-routes.php
 * Via Composer: ./run list:routes
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\RouteUrl;

$inventoryConfig = Yaml::parseFile(__DIR__ . '/inventory-config.yaml');
$routeProviders = $inventoryConfig['route_providers'];

$routes = [];

foreach ($routeProviders as $providerClass) {
    $shortName = new ReflectionEnum($providerClass)->getShortName();
    foreach ($providerClass::cases() as $route) {
        $routes[] = [
            'name'        => $route->name,
            'path'        => $route->value,
            'source'      => $shortName,
            'params'      => RouteUrl::paramsOf($route),
            'guard'       => Guarded::of($route)?->name,
            'description' => Route::descriptionOf($route),
            'operations'  => array_map(
                fn(Route $op): array => [
                    'method'      => $op->method()->value,
                    'description' => $op->description(),
                    'action'      => $op->actionName(),
                ],
                Route::on($route),
            ),
        ];
    }
}

echo json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
