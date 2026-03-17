<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Routes\Route;

final class AboutRouteTest extends IntegrationTestCase
{
    #[Test]
    public function rendersAboutPageAsHtml(): void
    {
        $ResponseInterface = $this->get(Route::about);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString('text/html', $ResponseInterface->getHeaderLine('Content-Type'));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('About Thryds', haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }
}
