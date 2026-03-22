<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\SchemaSource;

/**
 * Declares the schema synchronization source of truth for a Table class.
 *
 * Placed on classes carrying #[Table] to control how sync:schema resolves drift
 * between the live database and PHP #[Column] attributes.
 *
 * @example
 * #[SchemaSync(SchemaSource::attributes)]
 * #[Table(TableName: TableName::users, ...)]
 * readonly class User { ... }
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class SchemaSync
{
    public function __construct(
        public SchemaSource $SchemaSource,
    ) {}
}
