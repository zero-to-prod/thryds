<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which layout template a view extends.
 *
 * Applied to View enum cases. Replaces @extends() as the structural metadata source.
 *
 * @example
 * #[ExtendsLayout('base')]
 * case home = 'home';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ExtendsLayout
{
    public function __construct(
        public string $layout,
    ) {}
}
