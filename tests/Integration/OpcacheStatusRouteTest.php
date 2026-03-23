<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\DevRouteList;

#[CoversRoute(DevRouteList::opcache_status)]
final class OpcacheStatusRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonResponse(): void
    {
        $ResponseInterface = $this->get(DevRouteList::opcache_status);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));
    }
}
