<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\Validation\Rule;

#[Attribute(Attribute::TARGET_PROPERTY)]
readonly class Validate
{
    /** @var list<array{Rule, int|string|null}> */
    public array $rules;

    /** @param Rule|array{Rule, int|string|null} ...$rules */
    public function __construct(Rule|array ...$rules)
    {
        $this->rules = array_values(array_map(
            static fn(Rule|array $rule): array => $rule instanceof Rule
                ? [$rule, null]
                : $rule,
            $rules,
        ));
    }
}
