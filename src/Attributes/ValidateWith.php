<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a validation rule class for a property.
 *
 * The rule class must carry #[ValidationRule].
 */
#[Attribute(Attribute::TARGET_PROPERTY | Attribute::IS_REPEATABLE)]
readonly class ValidateWith
{
    /** @param class-string $rule */
    public function __construct(public string $rule) {}
}
