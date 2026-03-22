<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Queries\Persist;

/**
 * Declares a column with a persistence hook — generation or transformation.
 *
 * @see Persist
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
readonly class PersistColumn
{
    public function __construct(
        public string $column,
        public Persist $Persist,
    ) {}
}
