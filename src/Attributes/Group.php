<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use BackedEnum;

/**
 * Tags an enum case with a group value from a companion grouping enum.
 *
 * @example
 * #[Group(DevPathGroup::vendor)]
 * case phpunit = '/vendor/phpunit/';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class Group
{
    public function __construct(
        public BackedEnum $BackedEnum,
    ) {}
}
