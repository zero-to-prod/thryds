<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares which route a controller handles.
 *
 * The router discovers controllers via this attribute at boot time,
 * eliminating manual wiring in the route registrar.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class HandlesRoute
{
    public function __construct(public BackedEnum $BackedEnum) {}
}
