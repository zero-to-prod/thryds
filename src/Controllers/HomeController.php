<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Laminas\Diactoros\Response\HtmlResponse;
use ZeroToProd\Thryds\Blade\View;

/** Renders the home view. */
readonly class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse(
            html: blade()->make(view: View::home->value)->render(),
        );
    }
}
