<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Attributes\CoversRoute;
use ZeroToProd\Thryds\Attributes\Route;
use ZeroToProd\Thryds\Routes\RouteList;
use ZeroToProd\Thryds\Routes\RouteManifest;

#[CoversRoute(RouteList::routes)]
final class RoutesRouteTest extends IntegrationTestCase
{
    #[Test]
    public function returnsJsonManifestOfNonDevRoutes(): void
    {
        $ResponseInterface = $this->get(RouteList::routes);

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));

        $entries = json_decode((string) $ResponseInterface->getBody(), associative: true);
        $this->assertIsArray(actual: $entries);

        $paths = array_column(array: $entries, column_key: RouteManifest::path);
        $this->assertContains(RouteList::home->value, haystack: $paths);
        $this->assertNotContains(RouteList::routes->value, haystack: $paths);

        $first = $entries[0];
        $this->assertArrayHasKey(RouteManifest::name, array: $first);
        $this->assertArrayHasKey(RouteManifest::path, array: $first);
        $this->assertArrayHasKey(RouteManifest::description, array: $first);
        $this->assertArrayHasKey(RouteManifest::operations, array: $first);
        $this->assertIsArray(actual: $first[RouteManifest::operations]);

        $firstOp = $first[RouteManifest::operations][0];
        $this->assertArrayHasKey(RouteManifest::method, array: $firstOp);
        $this->assertArrayHasKey(RouteManifest::description, array: $firstOp);
        $this->assertSame(Route::on(RouteList::home)[0]->HttpMethod->value, $firstOp[RouteManifest::method]);
        $this->assertSame(Route::descriptionOf(RouteList::home), $first[RouteManifest::description]);
    }
}
