<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Declares a validation rule class for a property.
 *
 * The rule class must carry the validation rule attribute.
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
readonly class ValidateWith
{
    /** @param class-string $rule */
    public function __construct(public string $rule) {}
}
