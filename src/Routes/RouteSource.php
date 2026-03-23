<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\RouteEnum;
use ZeroToProd\Thryds\UI\Domain;

/**
 * Enumerates all route enum files. The registrar iterates these to discover routes.
 */
#[ClosedSet(
    Domain::route_sources,
    addCase: <<<TEXT
    1. Create a new BackedEnum in src/Routes/ with #[Route] attributes on cases.
    2. Add a case here with #[RouteEnum(NewEnum::class)].
    3. Run ./run fix:all.
    TEXT
)]
enum RouteSource
{
    #[RouteEnum(RouteList::class)]
    case RouteList;

    #[RouteEnum(DevRouteList::class)]
    case DevRouteList;
}
