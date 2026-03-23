<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares that a controller persists data to a model class.
 * Apply multiple times when a controller writes to more than one model.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[HopWeight(1)]
readonly class Persists
{
    /** @param class-string $model Fully-qualified model class name. */
    public function __construct(public string $model) {}
}
