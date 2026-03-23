<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the HTML page title for a view.
 *
 * Applied to View enum cases. Replaces @section('title', '...') as the structural metadata source.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class PageTitle
{
    public function __construct(
        public string $title,
    ) {}
}
