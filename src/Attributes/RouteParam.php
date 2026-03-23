<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use ReflectionAttribute;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Routes\RouteList;

/**
 * Declares a URL parameter on a Route enum case.
 * Apply once per {placeholder} in the route path so the parameter
 * schema is visible in the attribute graph without parsing the path string.
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT | Attribute::IS_REPEATABLE)]
readonly class RouteParam
{
    public function __construct(
        public string $name,
        public string $description,
    ) {}

    /** @return string[] Parameter names declared on a route case. */
    public static function on(RouteList $RouteList): array
    {
        /** @var array<string, string[]> $cache */
        static $cache = [];

        return $cache[$RouteList->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): string => $ReflectionAttribute->newInstance()->name,
            new ReflectionEnumUnitCase(RouteList::class, $RouteList->name)
                ->getAttributes(self::class),
        );
    }
}
