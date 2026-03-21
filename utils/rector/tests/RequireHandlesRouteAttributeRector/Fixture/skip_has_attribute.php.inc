<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

#[HandlesRoute(Route::home)]
readonly class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('home');
    }
}

?>
