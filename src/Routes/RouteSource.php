<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use BackedEnum;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Enumerates all route enum files. The registrar iterates these to discover routes.
 */
#[ClosedSet(
    Domain::route_sources,
    addCase: <<<TEXT
    1. Create a new BackedEnum in src/Routes/ with #[Route] attributes on cases.
    2. Add a case here pointing to the new enum class.
    3. Run ./run fix:all.
    TEXT
)]
enum RouteSource
{
    case RouteList;
    case DevRouteList;

    /** @return class-string<BackedEnum> */
    public function enumClass(): string
    {
        return match ($this) {
            self::RouteList => RouteList::class,
            self::DevRouteList => DevRouteList::class,
        };
    }
}
