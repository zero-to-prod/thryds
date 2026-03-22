<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Attribute;
use ZeroToProd\Thryds\Attributes\Infrastructure;

/**
 * Marks a class as a persistence hook resolver.
 *
 * Each class carrying this attribute handles a single transformation strategy
 * (e.g. ID generation, password hashing, timestamp generation).
 * Referenced by the #[ResolvesTo] attribute on Persist enum cases.
 *
 * Classes carrying this attribute must declare resolve(mixed $value): string.
 */
#[Attribute(Attribute::TARGET_CLASS)]
#[Infrastructure]
readonly class PersistResolver {}
