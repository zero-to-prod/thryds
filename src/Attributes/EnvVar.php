<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares which environment variable populates a property.
 *
 * Used by reflective factory methods to build configuration from environment
 * variables without hardcoding the mapping in method bodies.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class EnvVar
{
    public function __construct(
        public string $key,
    ) {}
}
