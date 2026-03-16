<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Helpers\View;

readonly class WebRoutes
{
    public static function register(Router $Router, Blade $Blade): void
    {
        $Router->map(
            'GET',
            HomeRoute::pattern,
            fn(): ResponseInterface => new HtmlResponse(
                html: $Blade->make(view: View::home)->render(),
            ),
        );

        $Router->map(
            'GET',
            AboutRoute::pattern,
            fn(): ResponseInterface => new HtmlResponse(
                html: $Blade->make(view: View::about)->render(),
            ),
        );

        $Router->map(
            'GET',
            OpcacheStatusRoute::pattern,
            static function (): ResponseInterface {
                $status = opcache_get_status(false);

                return new JsonResponse(
                    data: json_decode(
                        json_encode(value: $status, flags: JSON_PARTIAL_OUTPUT_ON_ERROR),
                        true,
                    ),
                );
            },
        );

        // TODO: [ForbidMagicStringArrayKeyRector] Constants name things. Define a public const with value 'scripts' on the appropriate class.
        $Router->map(
            'GET',
            OpcacheStatusRoute::scripts_pattern,
            static function (): ResponseInterface {
                $status = opcache_get_status(true);

                return new JsonResponse(
                    data: array_keys($status['scripts'] ?? []),
                );
            },
        );
    }
}
