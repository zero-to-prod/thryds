<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a DataType that requires a $values list on its column definition attribute.
 *
 * Applies to: ENUM, SET.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RequiresValues {}
