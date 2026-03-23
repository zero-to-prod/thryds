<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a class as a database migration and declares its ordered id and description.
 *
 * The id must match the four-digit prefix of the migration filename.
 * Validated at discovery time — mismatches throw a RuntimeException.
 *
 * @see \ZeroToProd\Framework\MigrationDiscovery
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Migration
{
    public function __construct(
        public string $id,
        public string $description,
    ) {}
}
