<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares an index on a table enum.
 *
 * Repeatable — use multiple #[Index] attributes for multiple indexes.
 * For single-column indexes, prefer placing #[Index] directly on the column case
 * via its own attribute; this class attribute is for composite (multi-column) indexes.
 *
 * The $columns array must contain the backed string values of the table enum cases
 * (i.e. the SQL column names), in index order.
 *
 * If $name is empty, DDL generators should derive a name from the table and columns
 * (e.g. idx_{table}_{col1}_{col2}).
 *
 * @example
 * #[Table(name: 'posts')]
 * #[Index(columns: ['user_id', 'created_at'])]
 * #[Index(columns: ['slug'], unique: true)]
 * enum PostTable: string { ... }
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class Index
{
    /** @param string[] $columns SQL column names (backed values of the enum cases), in index key order. */
    public function __construct(
        public array $columns,
        public bool $unique = false,
        public string $name = '',
    ) {}
}
