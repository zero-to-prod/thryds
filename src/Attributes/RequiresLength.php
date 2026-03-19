<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a DataType that requires a $length on its #[Column].
 *
 * Applies to: VARCHAR, CHAR, BINARY, VARBINARY.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RequiresLength {}
