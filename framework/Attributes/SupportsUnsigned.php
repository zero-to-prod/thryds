<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a DataType that supports $unsigned on its column definition attribute.
 *
 * Applies to: BIGINT, INT, SMALLINT, TINYINT, DECIMAL, FLOAT, DOUBLE.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class SupportsUnsigned {}
