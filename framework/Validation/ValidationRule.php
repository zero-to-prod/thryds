<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Validation;

use Attribute;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Marks a class as a validation rule.
 *
 * Classes carrying this attribute must declare:
 *   passes(mixed $value, object $context): bool
 *   message(string $field): string
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class ValidationRule {}
