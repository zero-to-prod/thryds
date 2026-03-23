<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Attribute;
use BackedEnum;
use ReflectionAttribute;
use ReflectionEnumUnitCase;

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
    public static function on(BackedEnum $BackedEnum): array
    {
        /** @var array<string, string[]> $cache */
        static $cache = [];

        return $cache[$BackedEnum::class . '::' . $BackedEnum->name] ??= array_map(
            static fn(ReflectionAttribute $ReflectionAttribute): string => $ReflectionAttribute->newInstance()->name,
            new ReflectionEnumUnitCase($BackedEnum::class, $BackedEnum->name)
                ->getAttributes(self::class),
        );
    }
}
