<?php

declare(strict_types=1);

/**
 * Print all registered routes as a JSON array.
 *
 * Each entry: { "name", "path", "dev_only", "description", "operations": [{ "method", "description" }] }
 *
 * Usage: docker compose exec web php scripts/list-routes.php
 * Via Composer: ./run list:routes
 *
 * Exit 0 on success.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Attributes\Route;
use ZeroToProd\Thryds\Routes\RouteList;

$routes = array_map(
    fn(RouteList $Route): array => [
        'name'        => $Route->name,
        'path'        => $Route->value,
        'params'      => $Route->params(),
        'dev_only'    => $Route->isDevOnly(),
        'description' => $Route->description(),
        'operations'  => array_map(
            fn(Route $op): array => [
                'method'      => $op->HttpMethod->value,
                'description' => $op->description,
                'action'      => $op->actionName(),
            ],
            $Route->operations(),
        ),
    ],
    RouteList::cases(),
);

echo json_encode($routes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
