<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Controllers;

use Jenssegers\Blade\Blade;
use Laminas\Diactoros\Response\HtmlResponse;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\Helpers\View;

readonly class HomeController
{
    public function __construct(private Blade $Blade) {}

    public function __invoke(): ResponseInterface
    {
        return new HtmlResponse(
            html: $this->Blade->make(view: View::home)->render(),
        );
    }
}
