<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use Attribute;
use BackedEnum;
use ReflectionEnumUnitCase;
use ZeroToProd\Thryds\Routes\RouteGuard;

/**
 * Applies a registration guard to a route or route file.
 *
 * When applied at the enum class level, all cases inherit the guard.
 * A case-level guard takes precedence over the class-level guard.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
readonly class Guarded
{
    public function __construct(public RouteGuard $RouteGuard) {}

    /** Resolve guard: case-level wins, then class-level, then null. */
    public static function of(BackedEnum $BackedEnum): ?RouteGuard
    {
        /** @var array<string, ?RouteGuard> $cache */
        static $cache = [];

        $key = $BackedEnum::class . '::' . $BackedEnum->name;

        if (!array_key_exists($key, array: $cache)) {
            $ReflectionEnumUnitCase = new ReflectionEnumUnitCase($BackedEnum::class, $BackedEnum->name);

            // Case-level takes precedence.
            $attrs = $ReflectionEnumUnitCase->getAttributes(self::class);
            if ($attrs !== []) {
                $cache[$key] = $attrs[0]->newInstance()->RouteGuard;

                return $cache[$key];
            }

            // Class-level (inherited by all cases).
            $attrs = $ReflectionEnumUnitCase->getEnum()->getAttributes(self::class);
            $cache[$key] = $attrs === [] ? null : $attrs[0]->newInstance()->RouteGuard;
        }

        return $cache[$key];
    }
}
