<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the primary key of a table enum.
 *
 * Single-column PK: place on the enum case alongside #[Column].
 * Composite PK: place on the enum class with $columns listing the SQL column names
 *               (the backed string values of the cases), in key order.
 *
 * @example Single-column PK:
 * #[Column(type: DataType::BIGINT, unsigned: true, autoIncrement: true)]
 * #[PrimaryKey]
 * case id = 'id';
 *
 * @example Composite PK on the class:
 * #[Table(name: 'post_tags')]
 * #[PrimaryKey(columns: ['post_id', 'tag_id'])]
 * enum PostTagTable: string { ... }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_PROPERTY)]
readonly class PrimaryKey
{
    /** @param string[] $columns SQL column names for a composite PK. Empty when used on a single column case. */
    public function __construct(
        public array $columns = [],
    ) {}
}
