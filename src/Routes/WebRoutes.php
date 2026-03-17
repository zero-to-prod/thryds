<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Controllers\HomeController;
use ZeroToProd\Thryds\Helpers\View;

readonly class WebRoutes
{
    /** OPcache status array key for cached scripts. @see opcache_get_status() */
    public const string scripts = 'scripts';

    public static function register(Router $Router, Blade $Blade): void
    {
        $Router->map(
            HTTP_METHOD::GET->value,
            Route::home->value,
            new HomeController($Blade),
        );

        $Router->map(
            HTTP_METHOD::GET->value,
            Route::about->value,
            fn(): ResponseInterface => new HtmlResponse(
                html: $Blade->make(view: View::about)->render(),
            ),
        );

        $Router->map(
            HTTP_METHOD::GET->value,
            Route::opcache_status->value,
            static fn(): ResponseInterface => new JsonResponse(
                data: json_decode(
                    json_encode(value: opcache_get_status(false), flags: JSON_PARTIAL_OUTPUT_ON_ERROR),
                    associative: true,
                ),
            ),
        );

        $Router->map(
            HTTP_METHOD::GET->value,
            Route::opcache_scripts->value,
            static fn(): ResponseInterface => new JsonResponse(
                data: array_keys(opcache_get_status(true)[WebRoutes::scripts] ?? []),
            ),
        );
    }
}
