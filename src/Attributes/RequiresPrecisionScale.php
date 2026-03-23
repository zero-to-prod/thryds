<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a DataType that requires $precision and $scale on its column definition attribute.
 *
 * Applies to: DECIMAL.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RequiresPrecisionScale {}
