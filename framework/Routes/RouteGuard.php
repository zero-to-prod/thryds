<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Config;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Registration predicates that determine whether a route enters the router.
 * A route guarded by a case that does not pass is never registered.
 */
#[ClosedSet(
    Domain::route_guards,
    addCase: <<<TEXT
    1. Add enum case.
    2. Add match arm in passes().
    3. Apply via #[Guarded(RouteGuard::newCase)] on the route.
    TEXT
)]
enum RouteGuard
{
    case devOnly;

    /** Evaluate whether this guard allows route registration under the given config. */
    public function passes(Config $Config): bool
    {
        return match ($this) {
            self::devOnly => !$Config->isProduction(),
        };
    }
}
