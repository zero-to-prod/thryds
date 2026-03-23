<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Routes\RouteGuard;
use ZeroToProd\Thryds\Routes\RouteList;

/**
 * Applies a registration guard to a route. The route is only registered when the guard passes.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class Guarded
{
    public function __construct(public RouteGuard $RouteGuard) {}

    /** Resolve the guard on a route case, or null if unguarded. */
    public static function of(RouteList $RouteList): ?RouteGuard
    {
        /** @var array<string, ?RouteGuard> $cache */
        static $cache = [];

        if (!array_key_exists($RouteList->name, array: $cache)) {
            $attrs = new ReflectionEnumUnitCase(RouteList::class, $RouteList->name)
                ->getAttributes(self::class);
            $cache[$RouteList->name] = $attrs === [] ? null : $attrs[0]->newInstance()->RouteGuard;
        }

        return $cache[$RouteList->name];
    }
}
