<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Schema\SchemaSource;

/**
 * Declares the schema synchronization source of truth for a Table class.
 *
 * Placed on classes carrying the table declaration attribute to control how sync:schema resolves drift
 * between the live database and column definition attributes.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[HopWeight(0)]
readonly class SchemaSync
{
    public function __construct(
        public SchemaSource $SchemaSource,
    ) {}
}
