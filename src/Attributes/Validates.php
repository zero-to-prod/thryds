<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Class-level validation rules scoped to a single property.
 *
 * Applied to request classes to declare form-specific validation
 * without re-declaring the property itself.
 *
 * @see Rule
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class Validates
{
    /** @var list<array{Rule, int|string|null}> */
    public array $rules;

    /** @param Rule|array{Rule, int|string|null} ...$rules */
    public function __construct(
        public string $property,
        Rule|array ...$rules,
    ) {
        $this->rules = array_values(array_map(
            static fn(Rule|array $rule): array => $rule instanceof Rule
                ? [$rule, null]
                : $rule,
            $rules,
        ));
    }
}
