<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;

/**
 * Marks a DataType that supports $auto_increment on its column definition attribute.
 *
 * Applies to: BIGINT, INT, SMALLINT, TINYINT.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class SupportsAutoIncrement {}
