<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Routes\Route;

final class OpcacheStatusRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonResponse(): void
    {
        $ResponseInterface = $this->get(Route::opcache_status);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));
    }
}
