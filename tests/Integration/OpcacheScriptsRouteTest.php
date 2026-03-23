<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Framework\Attributes\CoversRoute;
use ZeroToProd\Thryds\Routes\DevRouteList;

#[CoversRoute(DevRouteList::opcache_scripts)]
final class OpcacheScriptsRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonArrayResponse(): void
    {
        $ResponseInterface = $this->get(DevRouteList::opcache_scripts);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));
        $this->assertIsArray(json_decode((string) $ResponseInterface->getBody(), associative: true));
    }
}
