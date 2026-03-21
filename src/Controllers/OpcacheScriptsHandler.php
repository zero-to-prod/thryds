<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\OpcacheStatus;
use ZeroToProd\Thryds\Routes\Route;

#[HandlesRoute(Route::opcache_scripts)]
readonly class OpcacheScriptsHandler
{
    public function __invoke(): JsonResponse
    {
        return new JsonResponse(
            data: array_keys(opcache_get_status(true)[OpcacheStatus::scripts] ?? []),
        );
    }
}
