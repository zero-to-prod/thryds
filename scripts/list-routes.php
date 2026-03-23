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

use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Attributes\RouteParam;
use ZeroToProd\Thryds\Routes\RouteSource;

$routes = [];

foreach (RouteSource::cases() as $source) {
    foreach (\ZeroToProd\Framework\Attributes\RouteEnum::of($source)::cases() as $route) {
        $routes[] = [
            'name'        => $route->name,
            'path'        => $route->value,
            'source'      => $source->name,
            'params'      => RouteParam::on($route),
            'guard'       => Guarded::of($route)?->name,
            'description' => Route::descriptionOf($route),
            'operations'  => array_map(
                fn(Route $op): array => [
                    'method'      => $op->HttpMethod->value,
                    'description' => $op->description,
                    'action'      => $op->actionName(),
                ],
                Route::on($route),
            ),
        ];
    }
}

echo json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
