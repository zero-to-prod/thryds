<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a route as dev-only. Routes with this attribute are not registered in production.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
final readonly class DevOnly {}
