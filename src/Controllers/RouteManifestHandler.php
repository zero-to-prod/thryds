<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Thryds\Attributes\Guarded;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Route;
use ZeroToProd\Thryds\Attributes\RouteEnum;
use ZeroToProd\Thryds\Attributes\RouteParam;
use ZeroToProd\Thryds\Routes\DevRouteList;
use ZeroToProd\Thryds\Routes\RouteManifest;
use ZeroToProd\Thryds\Routes\RouteSource;

#[HandlesRoute(DevRouteList::routes)]
readonly class RouteManifestHandler
{
    public function __invoke(): JsonResponse
    {
        $entries = [];

        foreach (RouteSource::cases() as $source) {
            foreach (RouteEnum::of(UnitEnum: $source)::cases() as $route) {
                if (Guarded::of(BackedEnum: $route) !== null || RouteParam::on(BackedEnum: $route) !== []) {
                    continue;
                }

                $entries[] = [
                    RouteManifest::name        => $route->name,
                    RouteManifest::path        => $route->value,
                    RouteManifest::description => Route::descriptionOf(BackedEnum: $route),
                    RouteManifest::operations  => array_map(
                        static fn(Route $Route): array => [
                            RouteManifest::method      => $Route->HttpMethod->value,
                            RouteManifest::description => $Route->description,
                            RouteManifest::strategy    => $Route->actionName(),
                        ],
                        Route::on(BackedEnum: $route),
                    ),
                ];
            }
        }

        return new JsonResponse(data: $entries);
    }
}
