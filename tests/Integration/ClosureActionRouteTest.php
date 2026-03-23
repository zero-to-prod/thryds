<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Framework\App;
use ZeroToProd\Framework\AppEnv;
use ZeroToProd\Framework\Attributes\Route;
use ZeroToProd\Framework\Config;
use ZeroToProd\Framework\ConfigKey;
use ZeroToProd\Framework\DatabaseConfig;
use ZeroToProd\Framework\Routes\HttpMethod;
use ZeroToProd\Thryds\Tests\Unit\Fixtures\ClosureRouteList;

final class ClosureActionRouteTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->App = App::boot(
            base_dir: __DIR__ . '/../..',
            routeProviders: [ClosureRouteList::class],
            Config: Config::from([
                ConfigKey::AppEnv->value => AppEnv::development->value,
                ConfigKey::blade_cache_dir->value => $this->cache_dir,
                ConfigKey::template_dir->value => __DIR__ . '/../../templates',
                ConfigKey::DatabaseConfig->value => DatabaseConfig::from([]),
            ]),
        );
    }

    #[Test]
    public function closureActionDispatchesAndReturnsResponse(): void
    {
        $ResponseInterface = $this->App->Router->dispatch(
            new ServerRequest(
                serverParams: [],
                uploadedFiles: [],
                uri: new Uri(ClosureRouteList::ping->value),
                method: HttpMethod::GET->value,
            ),
        );

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));
        $this->assertTrue(json_decode((string) $ResponseInterface->getBody(), associative: true)[ClosureRouteList::RESPONSE_KEY_OK]);
    }

    #[Test]
    public function closureActionNameReturnsClosure(): void
    {
        $operations = Route::on(ClosureRouteList::ping);

        $this->assertCount(1, haystack: $operations);
        $this->assertSame(Route::ACTION_NAME_CLOSURE, $operations[0]->actionName());
    }
}
