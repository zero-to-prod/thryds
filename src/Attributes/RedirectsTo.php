<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares the route a controller redirects to on success.
 * Apply multiple times when a controller may redirect to different routes.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class RedirectsTo
{
    public function __construct(public BackedEnum $BackedEnum) {}
}
