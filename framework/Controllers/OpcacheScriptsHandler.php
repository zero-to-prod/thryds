<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Framework\Attributes\HandlesRoute;
use ZeroToProd\Framework\OpcacheStatus;
use ZeroToProd\Thryds\Routes\DevRouteList;

#[HandlesRoute(DevRouteList::opcache_scripts)]
readonly class OpcacheScriptsHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: array_keys(opcache_get_status(true)[OpcacheStatus::scripts] ?? []),
        );
    }
}
