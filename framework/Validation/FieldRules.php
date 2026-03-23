<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Validation;

use ReflectionClass;
use ZeroToProd\Framework\Attributes\Column;
use ZeroToProd\Framework\Attributes\Field;
use ZeroToProd\Framework\Attributes\Infrastructure;

/**
 * Merges column-derived validation rules with explicit rules from a Field attribute.
 *
 * Column-derived rules (required, max length) come from the Column attribute
 * on the referenced table property. Explicit rules on Field are additive.
 */
#[Infrastructure]
final class FieldRules
{
    /** @var array<string, ?Column> */
    private static array $column_cache = [];

    /**
     * @return list<array{Rule, int|string|null}>
     */
    public static function resolve(Field $Field): array
    {
        $rules = [];

        if ($Field->table !== null && $Field->column !== null) {
            $Column = self::resolveColumn($Field->table, $Field->column);

            if ($Column !== null) {
                if (!$Column->nullable && !$Field->optional) {
                    $rules[] = [Rule::required, null];
                }
                if ($Column->length !== null) {
                    $rules[] = [Rule::max, $Column->length];
                }
            }
        }

        foreach ($Field->normalized_rules as $rule) {
            if (!self::contains($rules, $rule[0])) {
                $rules[] = $rule;
            }
        }

        return $rules;
    }

    /**
     * @param class-string $table
     */
    private static function resolveColumn(string $table, string $column): ?Column
    {
        $cache_key = $table . '::' . $column;

        if (array_key_exists(key: $cache_key, array: self::$column_cache)) {
            return self::$column_cache[$cache_key];
        }

        $ReflectionClass = new ReflectionClass(objectOrClass: $table);

        foreach ($ReflectionClass->getProperties() as $property) {
            if ($property->getName() !== $column) {
                continue;
            }

            $column_attrs = $property->getAttributes(Column::class);
            if ($column_attrs === []) {
                return self::$column_cache[$cache_key] = null;
            }

            /** @var Column */
            return self::$column_cache[$cache_key] = $column_attrs[0]->newInstance();
        }

        return self::$column_cache[$cache_key] = null;
    }

    /**
     * @param list<array{Rule, int|string|null}> $rules
     */
    private static function contains(array $rules, Rule $Rule): bool
    {
        foreach ($rules as [$rule]) {
            if ($rule === $Rule) {
                return true;
            }
        }

        return false;
    }
}
