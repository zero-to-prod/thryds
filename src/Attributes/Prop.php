<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Declares a prop accepted by a Blade component.
 *
 * Applied to Component enum cases. Replaces @props() parsing as the structural metadata source.
 *
 * @example
 * #[Prop('variant', default: 'primary', enum: ButtonVariant::class)]
 * #[Prop('size', default: 'md', enum: ButtonSize::class)]
 * #[Prop('type', default: 'button')]
 * case button = 'button';
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
readonly class Prop
{
    /**
     * @param string $name Prop name as used in the template
     * @param string $default Default value (the resolved string, not the enum expression)
     * @param class-string|null $enum Backing enum class if the prop is enum-constrained, null otherwise
     */
    public function __construct(
        public string $name,
        public string $default,
        public ?string $enum,
    ) {}
}
