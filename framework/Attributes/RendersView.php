<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares which Blade view a controller renders.
 *
 * Applied to controller classes that render a template. The inventory
 * graph discovers view bindings via this attribute. Route-level view
 * bindings are declared via the view parameter on the route operation attribute.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class RendersView
{
    public function __construct(public BackedEnum $BackedEnum) {}
}
