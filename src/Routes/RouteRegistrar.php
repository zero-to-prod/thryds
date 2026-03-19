<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Controllers\HomeController;
use ZeroToProd\Thryds\OpcacheStatus;

readonly class RouteRegistrar
{
    public static function register(Router $Router, Blade $Blade, Config $Config): void
    {
        $Router->map(
            HttpMethod::GET->value,
            Route::home->value,
            new HomeController($Blade),
        );

        // Auto-register simple view routes by convention: Route::foo → View::foo (matched by name).
        // home is excluded — it uses HomeController above.
        foreach (Route::cases() as $Route) {
            $View = View::tryFrom($Route->name);
            if ($View !== null && $Route !== Route::home && (!$Route->isDevOnly() || !$Config->isProduction())) {
                $Router->map(
                    HttpMethod::GET->value,
                    $Route->value,
                    fn(): ResponseInterface => new HtmlResponse(
                        html: $Blade->make(view: $View->value)->render(),
                    ),
                );
            }
        }

        if (!$Config->isProduction()) {
            $Router->map(
                HttpMethod::GET->value,
                Route::opcache_status->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: json_decode(
                        (string) json_encode(value: opcache_get_status(false), flags: JSON_PARTIAL_OUTPUT_ON_ERROR),
                        associative: true,
                    ),
                ),
            );

            $Router->map(
                HttpMethod::GET->value,
                Route::opcache_scripts->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: array_keys(opcache_get_status(true)[OpcacheStatus::scripts] ?? []),
                ),
            );

            $Router->map(
                HttpMethod::GET->value,
                Route::routes->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: array_values(array_map(
                        fn(Route $Route): string => $Route->value,
                        array_filter(Route::cases(), fn(Route $Route): bool => !$Route->isDevOnly() && $Route->params() === []),
                    )),
                ),
            );
        }
    }

}
