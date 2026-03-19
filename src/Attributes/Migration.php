<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a class as a database migration and declares its ordered id and description.
 *
 * Usage: #[Migration(id: '0001', description: 'Create users table')]
 *
 * The id must match the four-digit prefix of the migration filename.
 * Validated by Migrator::discover() — mismatches throw a RuntimeException.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Migration
{
    public function __construct(
        public string $id,
        public string $description,
    ) {}
}
