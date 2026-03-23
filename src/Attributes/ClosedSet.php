<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Marks a backed enum as a closed set of allowed values in a specific domain.
 *
 * This is the single annotation for enums. For readonly classes, use the source-of-truth attribute instead.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class ClosedSet
{
    /**
     * @param Domain $Domain  The value domain this enum constrains.
     * @param string $addCase Human-readable checklist for what to do when adding a new enum case.
     */
    public function __construct(
        public Domain $Domain,
        public string $addCase,
    ) {}
}
