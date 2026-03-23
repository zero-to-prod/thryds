<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use ZeroToProd\Framework\UI\Props;

/**
 * Declares a prop accepted by a Blade component.
 *
 * Applied to Component enum cases. Replaces @props() parsing as the structural metadata source.
 * Pass a BackedEnum case as default to auto-derive the enum constraint.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
#[HopWeight(0)]
readonly class Prop
{
    /** Resolved default value from the backing enum or a plain string. */
    public int|string $default;

    /** @var class-string|null Backing enum class, derived when default is a BackedEnum. */
    public ?string $enum;

    public function __construct(
        public Props $Props,
        BackedEnum|string $default,
    ) {
        $this->default = is_string(value: $default) ? $default : $default->value;
        $this->enum = is_string(value: $default) ? null : $default::class;
    }
}
