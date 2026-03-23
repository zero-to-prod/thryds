<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a DataType that requires a $length on its column definition attribute.
 *
 * Applies to: VARCHAR, CHAR, BINARY, VARBINARY.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RequiresLength {}
