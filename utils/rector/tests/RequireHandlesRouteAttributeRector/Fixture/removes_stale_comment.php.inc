<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — HomeController in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
#[HandlesRoute(Route::home)]
readonly class HomeController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('home');
    }
}

?>
-----
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
