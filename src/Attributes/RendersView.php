<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Blade\View;

/**
 * Declares which Blade view a route or controller renders.
 *
 * Applied to Route enum cases for view-only routes and to controller
 * classes that render a template. The router and inventory graph
 * discover view bindings via this attribute.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
#[HopWeight(0)]
readonly class RendersView
{
    public function __construct(public View $View) {}
}
