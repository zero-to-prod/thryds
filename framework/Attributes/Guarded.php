<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use ReflectionEnumUnitCase;
use ZeroToProd\Framework\Routes\RouteGuard;

/**
 * Applies a registration guard to a route or route file.
 *
 * When applied at the enum class level, all cases inherit the guard.
 * A case-level guard takes precedence over the class-level guard.
 */
#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_CLASS_CONSTANT)]
readonly class Guarded
{
    use AttributeCache;

    public function __construct(public RouteGuard $RouteGuard) {}

    /** Resolve guard: case-level wins, then class-level, then null. */
    public static function of(BackedEnum $BackedEnum): ?RouteGuard
    {
        return self::cachedNullable('of', $BackedEnum::class . '::' . $BackedEnum->name, static function () use ($BackedEnum): ?RouteGuard {
            $ReflectionEnumUnitCase = new ReflectionEnumUnitCase($BackedEnum::class, $BackedEnum->name);

            // Case-level takes precedence.
            $attrs = $ReflectionEnumUnitCase->getAttributes(self::class);
            if ($attrs !== []) {
                return $attrs[0]->newInstance()->RouteGuard;
            }

            // Class-level (inherited by all cases).
            $attrs = $ReflectionEnumUnitCase->getEnum()->getAttributes(self::class);

            return $attrs === [] ? null : $attrs[0]->newInstance()->RouteGuard;
        });
    }
}
