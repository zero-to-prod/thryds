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
                static fn(RouteList $RouteList): array => [
                    RouteManifest::name        => $RouteList->name,
                    RouteManifest::path        => $RouteList->value,
                    RouteManifest::description => $RouteList->description(),
                    RouteManifest::operations  => array_map(
                        static fn(Route $Route): array => [
                            RouteManifest::method      => $Route->HttpMethod->value,
                            RouteManifest::description => $Route->description,
                            RouteManifest::strategy    => $Route->actionName(),
                        ],
                        $RouteList->operations(),
                    ),
                ],
                array_filter(RouteList::cases(), static fn(RouteList $RouteList): bool => !$RouteList->isDevOnly() && $RouteList->params() === []),
            )),
        );
    }
}
