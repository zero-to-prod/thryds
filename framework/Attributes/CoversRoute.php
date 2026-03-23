<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares which routes a test class covers.
 *
 * Applied to test classes. Replaces route-reference scanning as the structural metadata source.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class CoversRoute
{
    /** @var BackedEnum[] */
    public array $routes;

    public function __construct(BackedEnum ...$routes)
    {
        $this->routes = $routes;
    }
}
