<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\UI\Layout;

/**
 * Declares which layout template a view extends.
 *
 * Applied to View enum cases. Replaces @extends() as the structural metadata source.
 *
 * @example
 * #[ExtendsLayout(Layout::base)]
 * case home = 'home';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ExtendsLayout
{
    public string $layout;

    public function __construct(
        Layout $Layout,
    ) {
        $this->layout = $Layout->value;
    }
}
