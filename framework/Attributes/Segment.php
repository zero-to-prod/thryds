<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Overrides the namespace segment for a Layer case when it differs from the PascalCase of the case name.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class Segment
{
    public function __construct(
        public string $namespace,
    ) {}
}
