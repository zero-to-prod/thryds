<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use BackedEnum;
use ReflectionClass;
use ReflectionProperty;
use UnitEnum;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'toArray' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
/**
 * Project alias for {@see \Zerotoprod\DataModel\DataModel}.
 *
 * Provides `static from(array|object|null|string $context = []): self` — populates
 * public properties from array keys matching property names.
 *
 * Use the {@see Describe} attribute on properties to control population:
 * - `default`  — value when the key is missing (may be callable)
 * - `cast`     — custom casting function
 * - `from`     — map a different key name to this property
 * - `required` — throw if key is missing
 * - `nullable` — set to null if missing
 * - `pre`/`post` — hooks before/after casting
 * - `via`      — alternative instantiation method (default `from`)
 * - `ignore`   — skip the property entirely
 */
trait DataModel
{
    use \Zerotoprod\DataModel\DataModel;

    public function toArray(): array
    {
        $result = [];

        foreach (new ReflectionClass(objectOrClass: $this)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            if ($property->isStatic() || !$property->isInitialized(object: $this)) {
                continue;
            }

            $value = $property->getValue(object: $this);

            $result[$property->getName()] = match (true) {
                $value instanceof BackedEnum => $value->value,
                $value instanceof UnitEnum => $value->name,
                is_object($value) && method_exists(object_or_class: $value, method: 'toArray') => $value->toArray(),
                is_array($value) => array_map(
                    static fn(mixed $item): mixed => is_object(value: $item) && method_exists(object_or_class: $item, method: 'toArray')
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
