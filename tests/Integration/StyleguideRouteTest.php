<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\DevRouteList;

#[CoversRoute(DevRouteList::styleguide)]
final class StyleguideRouteTest extends IntegrationTestCase
{
    #[Test]
    public function rendersStyleguidePageAsHtml(): void
    {
        $ResponseInterface = $this->get(DevRouteList::styleguide);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::TEXT_HTML, $ResponseInterface->getHeaderLine('Content-Type'));

        $body = (string) $ResponseInterface->getBody();
        $this->assertStringContainsString('Styleguide', haystack: $body);
        $this->assertStringContainsString('</html>', haystack: $body);
    }
}
