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
use ZeroToProd\Thryds\Tests\Unit\Fixtures\ArrayCallableController;
use ZeroToProd\Thryds\Tests\Unit\Fixtures\InvokableController;
use ZeroToProd\Thryds\Tests\Unit\Fixtures\StringMethodRouteList;

final class StringMethodRouteTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->App = App::boot(
            base_dir: __DIR__ . '/../..',
            routeProviders: [StringMethodRouteList::class],
            Config: Config::from([
                ConfigKey::AppEnv->value => AppEnv::development->value,
                ConfigKey::blade_cache_dir->value => $this->cache_dir,
                ConfigKey::template_dir->value => __DIR__ . '/../../templates',
                ConfigKey::DatabaseConfig->value => DatabaseConfig::from([]),
            ]),
        );
    }

    #[Test]
    public function stringMethodWithClassStringActionDispatches(): void
    {
        $ResponseInterface = $this->App->Router->dispatch(
            new ServerRequest(
                serverParams: [],
                uploadedFiles: [],
                uri: new Uri(StringMethodRouteList::invokable->value),
                method: HttpMethod::GET->value,
            ),
        );

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertStringContainsString(self::APPLICATION_JSON, $ResponseInterface->getHeaderLine('Content-Type'));
        $this->assertTrue(json_decode((string) $ResponseInterface->getBody(), associative: true)[InvokableController::RESPONSE_KEY]);
    }

    #[Test]
    public function stringMethodWithArrayCallableActionDispatches(): void
    {
        $ResponseInterface = $this->App->Router->dispatch(
            new ServerRequest(
                serverParams: [],
                uploadedFiles: [],
                uri: new Uri(StringMethodRouteList::array_callable->value),
                method: HttpMethod::GET->value,
            ),
        );

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertTrue(json_decode((string) $ResponseInterface->getBody(), associative: true)[ArrayCallableController::RESPONSE_KEY]);
    }

    #[Test]
    public function stringMethodWithClosureActionDispatches(): void
    {
        $ResponseInterface = $this->App->Router->dispatch(
            new ServerRequest(
                serverParams: [],
                uploadedFiles: [],
                uri: new Uri(StringMethodRouteList::closure->value),
                method: HttpMethod::GET->value,
            ),
        );

        $this->assertSame(200, $ResponseInterface->getStatusCode());
        $this->assertTrue(json_decode((string) $ResponseInterface->getBody(), associative: true)[StringMethodRouteList::RESPONSE_KEY_CLOSURE]);
    }

    #[Test]
    public function stringMethodResolvesToHttpMethodEnum(): void
    {
        $operations = Route::on(StringMethodRouteList::invokable);

        $this->assertCount(1, haystack: $operations);
        $this->assertSame(HttpMethod::GET, $operations[0]->HttpMethod);
        $this->assertSame(HttpMethod::GET, $operations[0]->method());
    }

    #[Test]
    public function actionNameReportsCorrectly(): void
    {
        $this->assertSame('InvokableController', Route::on(StringMethodRouteList::invokable)[0]->actionName());
        $this->assertSame('ArrayCallableController::download', Route::on(StringMethodRouteList::array_callable)[0]->actionName());
        $this->assertSame(Route::ACTION_NAME_CLOSURE, Route::on(StringMethodRouteList::closure)[0]->actionName());
    }
}
