<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Routes\Route;

final class OpcacheScriptsRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonArrayResponse(): void
    {
        $ResponseInterface = $this->get(Route::opcache_scripts);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString('application/json', $ResponseInterface->getHeaderLine('Content-Type'));
        $this->assertIsArray(json_decode((string) $ResponseInterface->getBody(), associative: true));
    }
}
