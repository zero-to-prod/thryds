<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the target table, SET columns, and WHERE columns for an update query.
 *
 * Columns listed in $columns are SET from request object properties.
 * Columns listed in $where become positional WHERE = :column parameters.
 * Combine with {@see PersistColumn} for transformed values and {@see DbUpdate} trait for execution.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class UpdatesIn
{
    /**
     * @param class-string  $table   Table model class with HasTableName.
     * @param list<string>  $columns Column names to SET from the request.
     * @param list<string>  $where   Column names for the WHERE clause (positional).
     */
    public function __construct(
        public string $table,
        public array $columns,
        public array $where,
    ) {}
}
