<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;

/**
 * Contract for persistence hook resolvers.
 *
 * Each implementation handles a single transformation strategy
 * (e.g. ID generation, password hashing, timestamp generation).
 * Referenced by the #[ResolvesTo] attribute on Persist enum cases.
 */
#[Infrastructure]
interface PersistResolver
{
    public function resolve(mixed $value): string;
}
