<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\Route;
use ZeroToProd\Thryds\Routes\RouteList;
use ZeroToProd\Thryds\Routes\RouteManifest;

#[HandlesRoute(RouteList::routes)]
readonly class RouteManifestHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: array_values(array_map(
                static fn(RouteList $Route): array => [
                    RouteManifest::name        => $Route->name,
                    RouteManifest::path        => $Route->value,
                    RouteManifest::description => $Route->description(),
                    RouteManifest::operations  => array_map(
                        static fn(Route $RouteOperation): array => [
                            RouteManifest::method      => $RouteOperation->HttpMethod->value,
                            RouteManifest::description => $RouteOperation->description,
                            RouteManifest::strategy    => $RouteOperation->actionName(),
                        ],
                        $Route->operations(),
                    ),
                ],
                array_filter(RouteList::cases(), static fn(RouteList $Route): bool => !$Route->isDevOnly() && $Route->params() === []),
            )),
        );
    }
}
