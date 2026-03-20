<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\Route;

#[CoversRoute(Route::about)]
final class AboutRouteTest extends IntegrationTestCase
{
    #[Test]
    public function rendersAboutPageAsHtml(): void
    {
        $ResponseInterface = $this->get(Route::about);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine('Content-Type'));

        $haystack = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('About Thryds', $haystack);
        $this->assertStringContainsString('</html>', $haystack);
    }
}
