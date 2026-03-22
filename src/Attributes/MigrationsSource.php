<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares the filesystem directory and PHP namespace for migration classes.
 *
 * Placed on the Migrator class so the attribute graph captures migration
 * configuration instead of relying on external config for runtime wiring.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class MigrationsSource
{
    public function __construct(
        public string $directory,
        public string $namespace,
    ) {}
}
