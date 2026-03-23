<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use ZeroToProd\Framework\Schema\ReferentialAction;

/**
 * Declares the ON DELETE referential action for a foreign key column.
 *
 * Place on the same enum case as the foreign key declaration attribute.
 * Defaults to RESTRICT when omitted.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class OnDelete
{
    public function __construct(
        public ReferentialAction $ReferentialAction,
    ) {}
}
