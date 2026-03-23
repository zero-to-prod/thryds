<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use Attribute;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Marks a class as a persistence hook resolver.
 *
 * Each class carrying this attribute handles a single transformation strategy
 * (e.g. ID generation, password hashing, timestamp generation).
 * Referenced by the resolution target attribute on Persist enum cases.
 *
 * Classes carrying this attribute must declare resolve(mixed $value): string.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class PersistResolver {}
