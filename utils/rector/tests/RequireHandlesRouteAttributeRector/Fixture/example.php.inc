<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireHandlesRouteAttributeRector\Fixture;

// TODO: [RequireHandlesRouteAttributeRector] Attributes define properties — LoginController in Controllers/ is missing #[HandlesRoute]. Every controller must declare which route it handles so the router can discover it via reflection. See: utils/rector/docs/RequireHandlesRouteAttributeRector.md
readonly class LoginController
{
    public function __invoke(): HtmlResponse
    {
        return new HtmlResponse('login');
    }
}

?>
