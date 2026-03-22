<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a PDO connection option as a key-value pair.
 *
 * Applied to the Database class to make connection configuration
 * visible in the attribute graph instead of hardcoded in method bodies.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ConnectionOption
{
    public function __construct(
        public int $attribute,
        public mixed $value,
    ) {}
}
