<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;

/**
 * Marks a DataType that requires a $values list on its #[Column].
 *
 * Applies to: ENUM, SET.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RequiresValues {}
