<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the target table and columns for an insert query.
 *
 * Property name extracts columns listed from the request object.
 * Combine with {@see PersistColumn} for generated or transformed columns.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class InsertsInto
{
    /**
     * @param class-string $table   Table model class with HasTableName.
     * @param list<string> $columns Column names extracted from the request.
     */
    public function __construct(
        public string $table,
        public array $columns,
    ) {}
}
