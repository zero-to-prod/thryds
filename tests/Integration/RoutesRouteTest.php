<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Routes\Route;

final class RoutesRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonArrayOfNonDevRoutes(): void
    {
        $ResponseInterface = $this->get(Route::routes);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));

        $routes = json_decode((string) $ResponseInterface->getBody(), associative: true);
        $this->assertIsArray(actual: $routes);
        $this->assertContains(Route::home->value, haystack: $routes);
        $this->assertNotContains(Route::routes->value, haystack: $routes);
    }
}
