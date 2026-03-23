<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares the primary key of a table enum.
 *
 * Single-column PK: place on the enum case alongside the column declaration attribute.
 * Composite PK: place on the enum class with $columns listing the SQL column names
 *               (the backed string values of the cases), in key order.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
readonly class PrimaryKey
{
    /** @param string[] $columns SQL column names for a composite PK. Empty when used on a single column case. */
    public function __construct(
        public array $columns,
    ) {}
}
