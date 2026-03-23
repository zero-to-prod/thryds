<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Routes\RouteList;

final class RouteParamsTest extends TestCase
{
    private const string sort = 'sort';
    private const string id = 'id';

    #[Test]
    public function returnsEmptyArrayForStaticRoutes(): void
    {
        $this->assertSame([], RouteList::home->params());
        $this->assertSame([], RouteList::about->params());
    }

    #[Test]
    public function rendersStaticRouteWithoutParams(): void
    {
        $this->assertSame('/', RouteList::home->with()->render());
        $this->assertSame('/about', RouteList::about->with()->render());
    }

    #[Test]
    public function rendersQueryStringOnStaticRoute(): void
    {
        $this->assertSame('/about?sort=asc', RouteList::about->with(query: [self::sort => 'asc'])->render());
    }

    #[Test]
    public function throwsOnExtraParamsForStaticRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not accept params');

        RouteList::home->with(params: [self::id => '1'])->render();
    }
}
