<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ZeroToProd\Thryds\Attributes\Infrastructure;

// TODO: [ForbidInterfaceRector] Interfaces define implicit contracts — use PHP attributes to declare properties explicitly. Attributes are discoverable, enforceable, and composable without coupling. See: utils/rector/docs/ForbidInterfaceRector.md
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
