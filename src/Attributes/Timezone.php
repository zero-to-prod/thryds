<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

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
