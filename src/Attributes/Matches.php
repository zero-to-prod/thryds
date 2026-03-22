<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares that a property value must equal another property on the same model.
 *
 * Resolved by the Validator via reflection — the target property
 * is referenced by its constant name, not a magic string.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Matches
{
    public function __construct(public string $property) {}

    public function message(): string
    {
        return ucfirst(string: $this->property) . ' does not match.';
    }
}
