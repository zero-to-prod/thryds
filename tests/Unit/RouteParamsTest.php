<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Unit;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Framework\Attributes\RouteParam;
use ZeroToProd\Framework\Routes\RouteUrl;
use ZeroToProd\Thryds\Routes\RouteList;

final class RouteParamsTest extends TestCase
{
    private const string sort = 'sort';
    private const string id = 'id';

    #[Test]
    public function returnsEmptyArrayForStaticRoutes(): void
    {
        $this->assertSame([], RouteParam::on(RouteList::home));
        $this->assertSame([], RouteParam::on(RouteList::about));
    }

    #[Test]
    public function rendersStaticRouteWithoutParams(): void
    {
        $this->assertSame('/', RouteUrl::for(RouteList::home)->render());
        $this->assertSame('/about', RouteUrl::for(RouteList::about)->render());
    }

    #[Test]
    public function rendersQueryStringOnStaticRoute(): void
    {
        $this->assertSame('/about?sort=asc', RouteUrl::for(RouteList::about, query: [self::sort => 'asc'])->render());
    }

    #[Test]
    public function throwsOnExtraParamsForStaticRoute(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not accept params');

        RouteUrl::for(RouteList::home, params: [self::id => '1'])->render();
    }
}
