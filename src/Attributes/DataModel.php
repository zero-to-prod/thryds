<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use BackedEnum;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;
use UnitEnum;

/**
 * Project alias for {@see \Zerotoprod\DataModel\DataModel}.
 *
 * Provides `static from(array|object|null|string $context = []): self` — populates
 * public properties from array keys matching property names.
 *
 * Use the {@see Describe} attribute on properties to control population:
 * - `default`  — value when the key is missing (maybe callable)
 * - `cast`     — custom casting function
 * - `from`     — map a different key name to this property
 * - `required` — throw if key is missing
 * - `nullable` — set to null if missing
 * - `pre`/`post` — hooks before/after casting
 * - `via`      — alternative instantiation method (default `from`)
 * - `ignore`   — skip the property entirely
 */
#[Infrastructure]
trait DataModel
{
    use \Zerotoprod\DataModel\DataModel;
    private const string toArray = 'toArray';

    /**
     * @param  class-string  $class
     *
     * @return list<ReflectionProperty>
     * @throws ReflectionException
     */
    private static function publicInstanceProperties(string $class): array
    {
        /** @var array<class-string, list<ReflectionProperty>> $cache */
        static $cache = [];

        return $cache[$class] ??= array_values(array_filter(
            new ReflectionClass(objectOrClass: $class)->getProperties(ReflectionProperty::IS_PUBLIC),
            static fn(ReflectionProperty $ReflectionProperty): bool => !$ReflectionProperty->isStatic(),
        ));
    }

    /**
     * @return array<string, mixed>
     * @throws ReflectionException
     */
    public function toArray(): array
    {
        $result = [];

        foreach (self::publicInstanceProperties(static::class) as $property) {
            if (!$property->isInitialized(object: $this)) {
                continue;
            }

            $value = $property->getValue(object: $this);

            $result[$property->getName()] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum => $value->name,
                is_object($value) && method_exists(object_or_class: $value, method: self::toArray) => $value->toArray(),
                is_array($value) => array_map(
                    static fn(mixed $item): mixed => is_object(value: $item) && method_exists(object_or_class: $item, method: self::toArray)
                        ? $item->toArray()
                        : ($item instanceof BackedEnum ? $item->value : $item),
                    $value
                ),
                default => $value,
            };
        }

        return $result;
    }
}
