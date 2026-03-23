<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks an attribute class as a migration action that provides DDL operations.
 *
 * Applied to each DDL action attribute (table creation, column addition, column removal, raw SQL).
 * The Migrator dispatches generically on any attribute carrying this marker
 * — adding a new action attribute requires no changes to the Migrator itself.
 *
 * Classes carrying this attribute must declare upSql(): string and downSql(): string.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class MigrationAction {}
