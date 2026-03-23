<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Framework\Routes\FrameworkDevRouteList;
use ZeroToProd\Thryds\Attributes\HandlesRoute;

#[HandlesRoute(FrameworkDevRouteList::opcache_status)]
readonly class OpcacheStatusHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: json_decode(
                (string) json_encode(value: opcache_get_status(false), flags: JSON_PARTIAL_OUTPUT_ON_ERROR),
                associative: true,
            ),
        );
    }
}
