<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\ReferentialAction;

/**
 * Declares the ON UPDATE referential action for a foreign key column.
 *
 * Place on the same enum case as the foreign key declaration attribute.
 * Defaults to RESTRICT when omitted.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class OnUpdate
{
    public function __construct(
        public ReferentialAction $ReferentialAction,
    ) {}
}
