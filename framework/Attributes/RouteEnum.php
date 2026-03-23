<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use ReflectionEnumUnitCase;
use UnitEnum;

/**
 * Declares the route enum class a RouteSource case points to.
 *
 * @param class-string<BackedEnum> $class
 */
#[Attribute(Attribute::TARGET_CLASS_CONSTANT)]
readonly class RouteEnum
{
    /** @param class-string<BackedEnum> $class */
    public function __construct(public string $class) {}

    /** @return class-string<BackedEnum> */
    public static function of(UnitEnum $UnitEnum): string
    {
        /** @var array<string, class-string<BackedEnum>> $cache */
        static $cache = [];

        return $cache[$UnitEnum::class . '::' . $UnitEnum->name] ??= new ReflectionEnumUnitCase($UnitEnum::class, $UnitEnum->name)
            ->getAttributes(self::class)[0]
            ->newInstance()
            ->class;
    }
}
