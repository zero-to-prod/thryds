<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Framework\Attributes\Guarded;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Routes\FrameworkDevRouteList;
use ZeroToProd\Framework\Routes\RouteManifest;
use ZeroToProd\Framework\Routes\RouteRegistrar;
use ZeroToProd\Framework\Routes\RouteUrl;
use ZeroToProd\Thryds\Attributes\HandlesRoute;

#[HandlesRoute(FrameworkDevRouteList::routes)]
readonly class RouteManifestHandler
{
    public function __invoke(): JsonResponse
    {
        $entries = [];

        foreach (RouteRegistrar::providers() as $providerClass) {
            foreach ($providerClass::cases() as $route) {
                if (Guarded::of(BackedEnum: $route) !== null || RouteUrl::paramsOf(BackedEnum: $route) !== []) {
                    continue;
                }

                $entries[] = [
                    RouteManifest::name        => $route->name,
                    RouteManifest::path        => $route->value,
                    RouteManifest::description => Route::descriptionOf(BackedEnum: $route),
                    RouteManifest::operations  => array_map(
                        static fn(Route $Route): array => [
                            RouteManifest::method      => $Route->method()->value,
                            RouteManifest::description => $Route->description(),
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
