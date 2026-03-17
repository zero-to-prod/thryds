<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\APP_ENV;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Routes\Route;

/**
 * Base class for integration tests that exercise routes through the full App stack.
 *
 * 1. Create a file in tests/Integration/ with a class extending IntegrationTestCase.
 * 2. Use the #[Test] attribute and declare(strict_types=1).
 * 3. Call $this->get(Route::case_name) or $this->post(Route::case_name).
 * 4. Assert on ResponseInterface: getStatusCode(), getHeaderLine(), (string) getBody().
 * 5. Always reference routes via Route::case_name — RequireRouteTestRector scans tests
 *    for Route:: references to track coverage and remove TODO comments.
 */
abstract class IntegrationTestCase extends TestCase
{
    private const string base_dir = __DIR__ . '/../..';
    protected App $App;
    protected string $cache_dir;

    protected function setUp(): void
    {
        $this->cache_dir = sys_get_temp_dir() . '/thryds_test_' . uniqid('', more_entropy: true);
        mkdir($this->cache_dir, 0o755, recursive: true);
        $this->App = App::boot(self::base_dir, Config::from([
            Config::APP_ENV => APP_ENV::development->value,
            Config::blade_cache_dir => $this->cache_dir,
            Config::template_dir => self::base_dir . '/templates',
        ]));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cache_dir . '/*.php') as $file) {
            unlink(filename: $file);
        }
        rmdir($this->cache_dir);
    }

    protected function get(Route $Route): ResponseInterface
    {
        return $this->App->Router->dispatch(
            new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri($Route->value), method: 'GET'),
        );
    }

    protected function post(Route $Route): ResponseInterface
    {
        return $this->App->Router->dispatch(
            new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri($Route->value), method: 'POST'),
        );
    }
}
