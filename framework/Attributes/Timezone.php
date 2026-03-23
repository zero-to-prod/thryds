<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares the session timezone applied on each new database connection.
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Timezone
{
    public function __construct(
        public string $timezone,
    ) {}
}
