<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Framework\OpcacheStatus;
use ZeroToProd\Framework\Routes\FrameworkDevRouteList;
use ZeroToProd\Thryds\Attributes\HandlesRoute;

#[HandlesRoute(FrameworkDevRouteList::opcache_scripts)]
readonly class OpcacheScriptsHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: array_keys(opcache_get_status(true)[OpcacheStatus::scripts] ?? []),
        );
    }
}
