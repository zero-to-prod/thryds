<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\UI\InputType;
use ZeroToProd\Thryds\Validation\Rule;

/**
 * Unified property-level attribute declaring data source, input rendering, and validation rules.
 *
 * When $table and $column are provided, baseline rules (required, max length) are derived
 * from the column's schema definition. Explicit $rules are additive.
 *
 * @see Rule
 * @see Column
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[HopWeight(0)]
readonly class Field
{
    /** @var list<array{Rule, int|string|null}> */
    public array $normalized_rules;

    /**
     * @param class-string|null $table  Source table class (null = no backing column).
     * @param string|null       $column Column constant from that table.
     * @param list<Rule|array{Rule, int|string|null}> $rules Validation rules beyond what the column implies.
     * @param bool              $optional When true, suppresses column-derived required rule.
     */
    public function __construct(
        public ?string $table,
        public ?string $column,
        public InputType $InputType,
        public string $label,
        public int $order,
        public array $rules,
        public bool $optional,
    ) {
        $this->normalized_rules = array_map(
            static fn(Rule|array $rule): array => $rule instanceof Rule
                ? [$rule, null]
                : $rule,
            $rules,
        );
    }
}
