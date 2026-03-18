<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\RequestId;

final class SEC001Test extends TestCase
{
    protected function setUp(): void
    {
        RequestId::reset();
    }

    protected function tearDown(): void
    {
        RequestId::reset();
    }

    #[Test]
    // Criterion: SEC-001-a — The current request ID is null when no request is in flight
    public function test_SEC_001_a(): void
    {
        $this->assertNull(RequestId::current());
    }

    #[Test]
    // Criterion: SEC-001-b — The current request ID is a non-null string after a request begins and before it ends
    public function test_SEC_001_b(): void
    {
        RequestId::init(new ServerRequest());

        $this->assertIsString(RequestId::current());
        $this->assertNotEmpty(RequestId::current());
    }

    #[Test]
    // Criterion: SEC-001-c — The current request ID is null after a request ends
    public function test_SEC_001_c(): void
    {
        RequestId::init(new ServerRequest());
        RequestId::reset();

        $this->assertNull(RequestId::current());
    }
}
