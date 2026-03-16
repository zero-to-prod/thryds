<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
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
    }
}
