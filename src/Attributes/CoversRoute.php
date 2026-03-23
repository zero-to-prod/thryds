<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Routes\RouteList;

/**
 * Declares which routes a test class covers.
 *
 * Applied to test classes. Replaces route-reference scanning as the structural metadata source.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class CoversRoute
{
    /** @var RouteList[] */
    public array $routes;

    public function __construct(RouteList ...$routes)
    {
        $this->routes = $routes;
    }
}
