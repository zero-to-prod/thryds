<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ZeroToProd\Thryds\UI\InputType;

/**
 * Declares HTML input metadata for a request property.
 *
 * Applied to properties on request classes to describe how the field
 * should be rendered as a form input. The `name` attribute and `required`
 * flag are derived from the property name and {@see Validates} presence.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
#[HopWeight(0)]
readonly class Input
{
    public function __construct(
        public InputType $InputType,
        public string $label,
        public int $order,
    ) {}
}
