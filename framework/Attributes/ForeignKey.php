<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a foreign key constraint on a table enum case (column).
 *
 * The $BackedEnum parameter takes a backed enum case from the target table enum,
 * making the relationship navigable in both directions: an agent or IDE can follow
 * the case directly to the referenced column definition. The target table name is
 * derived from the table declaration attribute on that enum class.
 *
 * Use the referential action attributes on the same case to declare ON DELETE / ON UPDATE behavior.
 * Both default to RESTRICT when omitted.
 *
 * If $name is empty, DDL generators should derive a name from the source and target
 * (e.g., fk_{source_table}_{column}_{target_table}).
 *
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ForeignKey
{
    public function __construct(
        /** A backed enum case from the target table representing the referenced column. */
        public BackedEnum $BackedEnum,
        /** Constraint name. Auto-generated from source/target if empty. */
        public string $name,
    ) {}
}
