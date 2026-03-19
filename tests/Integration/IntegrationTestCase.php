<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Route\Http\Exception as HttpException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ZeroToProd\Thryds\App;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Blade\View;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Header;
use ZeroToProd\Thryds\RequestId;
use ZeroToProd\Thryds\Routes\HttpMethod;
use ZeroToProd\Thryds\Routes\Route;
use ZeroToProd\Thryds\ViewModels\ErrorViewModel;

/**
 * Base class for integration tests that exercise routes through the full App stack.
 *
 * 1. Create a file in tests/Integration/ with a class extending IntegrationTestCase.
 * 2. Use the #[Test] attribute and declare(strict_types=1).
 * 3. Call $this->get(Route::case_name) or $this->post(Route::case_name).
 * 4. Assert on ResponseInterface: getStatusCode(), getHeaderLine(), (string) getBody().
 * 5. Always reference routes via Route::case_name — RequireRouteTestRector scans tests
 *    for Route:: references to track coverage.
 */
abstract class IntegrationTestCase extends TestCase
{
    private const string base_dir = __DIR__ . '/../..';
    protected const string TEXT_HTML = 'text/html';
    protected const string APPLICATION_JSON = 'application/json';
    protected App $App;
    protected string $cache_dir;

    protected function setUp(): void
    {
        $this->cache_dir = sys_get_temp_dir() . '/thryds_test_' . uniqid('', more_entropy: true);
        mkdir($this->cache_dir, 0o755, recursive: true);
        $this->App = App::boot(self::base_dir, Config::from([
            Config::AppEnv => AppEnv::development->value,
            Config::blade_cache_dir => $this->cache_dir,
            Config::template_dir => self::base_dir . '/templates',
        ]), new DatabaseConfig(host: '0.0.0.0', port: 0, database: '', username: '', password: ''));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->cache_dir . '/*.php') as $file) {
            unlink(filename: $file);
        }
        rmdir($this->cache_dir);
    }

    /** @param array<string, string[]> $headers */
    protected function dispatch(Route $Route, array $headers = [], HttpMethod $HttpMethod = HttpMethod::GET): ResponseInterface
    {
        $ServerRequest = new ServerRequest(
            serverParams: [],
            uploadedFiles: [],
            uri: new Uri($Route->value),
            method: $HttpMethod->value,
            headers: $headers,
        );

        return $this->App->Router->dispatch(request: $ServerRequest)
            ->withHeader(Header::request_id, RequestId::init(ServerRequestInterface: $ServerRequest));
    }

    /**
     * @throws InvalidArgumentException
     */
    protected function get(Route $Route): ResponseInterface
    {
        return $this->App->Router->dispatch(
            new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri($Route->value), method: HttpMethod::GET->value),
        );
    }

    protected function post(Route $Route): ResponseInterface
    {
        return $this->App->Router->dispatch(
            new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri($Route->value), method: HttpMethod::POST->value),
        );
    }

    protected function assertErrorResponse(int $expected_status, string $expected_message, Route $Route, HttpMethod $HttpMethod = HttpMethod::GET): void
    {
        try {
            $this->App->Router->dispatch(
                new ServerRequest(serverParams: [], uploadedFiles: [], uri: new Uri($Route->value), method: $HttpMethod->value),
            );
            $this->fail("Expected HttpException with status $expected_status, but no exception was thrown.");
        } catch (HttpException $HttpException) {
            $this->assertSame(expected: $expected_status, actual: $HttpException->getStatusCode());

            $body = new HtmlResponse(
                html: $this->App->Blade->make(view: View::error->value, data: [
                    ErrorViewModel::view_key => ErrorViewModel::from([
                        ErrorViewModel::message => $HttpException->getMessage(),
                        ErrorViewModel::status_code => $HttpException->getStatusCode(),
                    ]),
                ])->render(),
                status: $HttpException->getStatusCode(),
            )->getBody()->__toString();

            $this->assertStringContainsString(needle: $expected_message, haystack: $body);
            $this->assertStringContainsString((string) $expected_status, haystack: $body);
        }
    }
}
