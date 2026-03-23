<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\RouteList;

#[CoversRoute(RouteList::login)]
final class LoginRouteTest extends IntegrationTestCase
{
    #[Test]
    public function rendersLoginPageAsHtml(): void
    {
        $ResponseInterface = $this->get(RouteList::login);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine('Content-Type'));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('Login', haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }
}
