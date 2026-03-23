<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Config;
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
