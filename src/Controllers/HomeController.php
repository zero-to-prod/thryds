<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;
use ZeroToProd\Thryds\Attributes\HandlesRoute;
use ZeroToProd\Thryds\Attributes\RendersView;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Routes\Route;

/** Renders the home view. */
#[HandlesRoute(Route::home)]
#[RendersView(View::home)]
readonly class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse(
            html: blade()->make(view: View::home->value)->render(),
        );
    }
}
