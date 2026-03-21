<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Attributes\RouteOperation;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Controllers\HomeController;
use ZeroToProd\Thryds\Controllers\RegisterController;
use ZeroToProd\Thryds\OpcacheStatus;

readonly class RouteRegistrar
{
    public static function register(Router $Router, Config $Config): void
    {
        $Router->map(
            Route::home->operations()[0]->HttpMethod->value,
            Route::home->value,
            new HomeController(),
        );

        $RegisterController = new RegisterController();
        foreach (Route::register->operations() as $op) {
            $Router->map($op->HttpMethod->value, Route::register->value, handler: $RegisterController);
        }

        // Auto-register simple view routes by convention: Route::foo → View::foo (matched by name).
        // home and register are excluded — they use explicit controllers above.
        foreach (Route::cases() as $Route) {
            $View = View::tryFrom($Route->name);
            if ($View !== null && $Route !== Route::home && $Route !== Route::register && (!$Route->isDevOnly() || !$Config->isProduction())) {
                $Router->map(
                    $Route->operations()[0]->HttpMethod->value,
                    $Route->value,
                    fn(): ResponseInterface => new HtmlResponse(
                        html: blade()->make(view: $View->value)->render(),
                    ),
                );
            }
        }

        if (!$Config->isProduction()) {
            $Router->map(
                Route::opcache_status->operations()[0]->HttpMethod->value,
                Route::opcache_status->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: json_decode(
                        (string) json_encode(value: opcache_get_status(false), flags: JSON_PARTIAL_OUTPUT_ON_ERROR),
                        associative: true,
                    ),
                ),
            );

            $Router->map(
                Route::opcache_scripts->operations()[0]->HttpMethod->value,
                Route::opcache_scripts->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: array_keys(opcache_get_status(true)[OpcacheStatus::scripts] ?? []),
                ),
            );

            $Router->map(
                Route::routes->operations()[0]->HttpMethod->value,
                Route::routes->value,
                static fn(): ResponseInterface => new JsonResponse(
                    data: array_values(array_map(
                        fn(Route $Route): array => [
                            RouteManifest::name        => $Route->name,
                            RouteManifest::path        => $Route->value,
                            RouteManifest::description => $Route->description(),
                            RouteManifest::operations  => array_map(
                                fn(RouteOperation $RouteOperation): array => [
                                    RouteManifest::method      => $RouteOperation->HttpMethod->value,
                                    RouteManifest::description => $RouteOperation->description,
                                ],
                                $Route->operations(),
                            ),
                        ],
                        array_filter(Route::cases(), fn(Route $Route): bool => !$Route->isDevOnly() && $Route->params() === []),
                    )),
                ),
            );
        }
    }

}
