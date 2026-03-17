<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Routes\Route;

final class RouteParamsTest extends TestCase
{
    private const string sort = 'sort';
    private const string id = 'id';

    #[Test]
    public function returnsEmptyArrayForStaticRoutes(): void
    {
        $this->assertSame([], Route::home->params());
        $this->assertSame([], Route::about->params());
    }

    #[Test]
    public function rendersStaticRouteWithoutParams(): void
    {
        $this->assertSame('/', Route::home->with()->render());
        $this->assertSame('/about', Route::about->with()->render());
    }

    #[Test]
    public function rendersQueryStringOnStaticRoute(): void
    {
        $this->assertSame('/about?sort=asc', Route::about->with(query: [self::sort => 'asc'])->render());
    }

    #[Test]
    public function throwsOnExtraParamsForStaticRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not accept params');

        Route::home->with(params: [self::id => '1'])->render();
    }
}
