<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\Route;

#[CoversRoute(Route::home)]
final class HomeControllerTest extends IntegrationTestCase
{
    #[Test]
    public function rendersHomePageAsHtml(): void
    {
        $ResponseInterface = $this->get(Route::home);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine('Content-Type'));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('<h1>Thryds', haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }
}
