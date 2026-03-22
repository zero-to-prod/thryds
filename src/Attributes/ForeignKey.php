<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use BackedEnum;

/**
 * Declares a foreign key constraint on a table enum case (column).
 *
 * The $BackedEnum parameter takes a backed enum case from the target table enum:
 * E.g., RoleTable::id. This makes the relationship navigable in both directions:
 * an AI agent or IDE can follow RoleTable::id directly to the referenced column definition.
 * The target table name is derived from the #[Table] attribute on that enum class.
 *
 * Use #[OnDelete] and #[OnUpdate] in the same case to declare referential actions.
 * Both default to RESTRICT when omitted.
 *
 * If $name is empty, DDL generators should derive a name from the source and target
 * (e.g., fk_{source_table}_{column}_{target_table}).
 *
 * @example
 * #[Column(DataType: DataType::BIGINT, unsigned: true, nullable: true)]
 * #[ForeignKey(BackedEnum: RoleTable::id)]
 * #[OnDelete(ReferentialAction: ReferentialAction::SET_NULL)]
 * case role_id = 'role_id';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class ForeignKey
{
    public function __construct(
        /** Target a column as a table-enum case, e.g., RoleTable::id. Navigate here for the full column definition. */
        public BackedEnum $BackedEnum,
        /** Constraint name. Auto-generated from source/target if empty. */
        public string $name,
    ) {}
}
