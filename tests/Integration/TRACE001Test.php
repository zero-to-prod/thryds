<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Integration;

use PHPUnit\Framework\Attributes\Test;
use ZeroToProd\Thryds\Header;
use ZeroToProd\Thryds\RequestId;
use ZeroToProd\Thryds\Routes\Route;

final class TRACE001Test extends IntegrationTestCase
{
    #[Test]
    // Criterion: TRACE-001-a — Every dispatched response includes a non-empty X-Request-ID header
    public function test_TRACE_001_a(): void
    {
        $this->assertNotEmpty($this->dispatch(Route::home)->getHeaderLine(Header::request_id));
    }

    #[Test]
    // Criterion: TRACE-001-b — A request that carries an X-Request-ID header echoes the same value in the response
    public function test_TRACE_001_b(): void
    {
        $incoming_id = 'abc123-correlation';

        $this->assertSame(expected: $incoming_id, actual: $this->dispatch(Route::home, headers: [Header::request_id => [$incoming_id]])->getHeaderLine(Header::request_id));
    }

    #[Test]
    // Criterion: TRACE-001-c — A request without an X-Request-ID header receives a 32-character lowercase hex string
    public function test_TRACE_001_c(): void
    {
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{32}$/',
            $this->dispatch(Route::home)->getHeaderLine(Header::request_id),
        );
    }

    protected function tearDown(): void
    {
        RequestId::reset();
        parent::tearDown();
    }
}
