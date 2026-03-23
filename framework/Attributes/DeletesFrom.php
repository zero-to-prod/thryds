<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares the target table and WHERE columns for a delete query.
 *
 * Columns listed in $where become positional WHERE = :column parameters.
 * Combine with {@see DbDelete} trait for attribute-driven DELETE execution.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class DeletesFrom
{
    /**
     * @param class-string  $table Table model class with HasTableName.
     * @param list<string>  $where Column names for the WHERE clause (positional).
     */
    public function __construct(
        public string $table,
        public array $where,
    ) {}
}
