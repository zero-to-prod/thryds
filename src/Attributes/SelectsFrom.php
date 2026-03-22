<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the target table, columns, and optional WHERE columns for a select query.
 *
 * Columns listed in $columns become the SELECT list.
 * Columns listed in $where become positional WHERE = :column parameters.
 * Combine with {@see DbRead} trait for attribute-driven SELECT execution.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SelectsFrom
{
    /**
     * @param class-string  $table   Table model class with HasTableName.
     * @param list<string>  $columns Column names to SELECT.
     * @param list<string>  $where   Column names for the WHERE clause (positional).
     * @param string        $order_by Column name for ORDER BY clause. Empty for unordered.
     */
    public function __construct(
        public string $table,
        public array $columns,
        public array $where,
        public string $order_by,
    ) {}
}
