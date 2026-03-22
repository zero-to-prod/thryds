<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares an error message substring that triggers automatic reconnection.
 *
 * When a PDOException message contains the declared substring,
 * the Database class will reconnect and retry the failed statement.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class ReconnectOn
{
    public function __construct(
        public string $message,
    ) {}
}
