<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use Closure;

/**
 * Standardizes the static-cache-per-enum-case pattern used by attribute resolvers.
 *
 * Each using class gets its own static cache (PHP trait scoping).
 * The $slot parameter separates caches when a class has multiple cached methods.
 */
#[Infrastructure]
trait AttributeCache
{
    /**
     * @template T
     *
     * @param Closure(): T $Closure
     *
     * @return T
     */
    private static function cached(string $slot, string $key, Closure $Closure): mixed
    {
        /** @var array<string, array<string, mixed>> $cache */
        static $cache = [];

        return $cache[$slot][$key] ??= $Closure();
    }

    /**
     * @template T
     *
     * @param Closure(): T $Closure
     *
     * @return T
     */
    private static function cachedNullable(string $slot, string $key, Closure $Closure): mixed
    {
        /** @var array<string, array<string, mixed>> $cache */
        static $cache = [];

        if (!array_key_exists($key, $cache[$slot] ?? [])) {
            $cache[$slot][$key] = $Closure();
        }

        return $cache[$slot][$key];
    }
}
