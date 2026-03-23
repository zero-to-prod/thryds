<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;

/**
 * Tags an enum case with a group value from a companion grouping enum.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
#[HopWeight(0)]
readonly class Group
{
    public function __construct(
        public BackedEnum $BackedEnum,
    ) {}
}
