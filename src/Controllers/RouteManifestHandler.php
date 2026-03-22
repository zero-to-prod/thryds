<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\Routes\RouteManifest;

#[HandlesRoute(Route::routes)]
readonly class RouteManifestHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: array_values(array_map(
                static fn(Route $Route): array => [
                    RouteManifest::name        => $Route->name,
                    RouteManifest::path        => $Route->value,
                    RouteManifest::description => $Route->description(),
                    RouteManifest::operations  => array_map(
                        static fn(RouteOperation $RouteOperation): array => [
                            RouteManifest::method      => $RouteOperation->HttpMethod->value,
                            RouteManifest::description => $RouteOperation->description,
                            RouteManifest::strategy    => $RouteOperation->HandlerStrategy->value,
                        ],
                        $Route->operations(),
                    ),
                ],
                array_filter(Route::cases(), static fn(Route $Route): bool => !$Route->isDevOnly() && $Route->params() === []),
            )),
        );
    }
}
