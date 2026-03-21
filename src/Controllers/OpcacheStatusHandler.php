<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\JsonResponse;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Routes\Route;

#[HandlesRoute(Route::opcache_status)]
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
