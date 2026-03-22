<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\InsertsInto;
use ZeroToProd\Thryds\Attributes\PersistColumn;
use ZeroToProd\Thryds\Database;

/**
 * Attribute-driven INSERT execution.
 *
 * Reads {@see InsertsInto} and {@see PersistColumn} from the using class,
 * extracts column values from request objects, applies persistence hooks,
 * and executes the INSERT statement.
 */
#[Infrastructure]
trait DbCreate
{
    /** @throws RandomException */
    public static function create(object $request, ?Database $Database = null): void
    {
        [$all_columns, $hooks, $table_name] = self::resolveInsertMeta();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        ($Database ?? db())->execute(Sql::INSERT_INTO . $table_name
            . ' (' . implode(', ', array: $all_columns) . ')'
            . ' VALUES (' . implode(', ', array_map(
                static fn(string $column): string => ':' . $column,
                $all_columns,
            )) . ')', self::extractParams($request, $all_columns, $hooks));
    }

    /** @throws RandomException */
    public static function createMany(object ...$requests): void
    {
        if ($requests === []) {
            return;
        }

        [$all_columns, $hooks, $table_name] = self::resolveInsertMeta();

        $params = [];
        $value_groups = [];
        foreach ($requests as $index => $request) {
            $row_params = self::extractParams($request, $all_columns, $hooks);
            $placeholders = [];
            foreach ($row_params as $placeholder => $value) {
                $indexed_placeholder = $placeholder . '_' . $index;
                $params[$indexed_placeholder] = $value;
                $placeholders[] = $indexed_placeholder;
            }
            $value_groups[] = '(' . implode(', ', array: $placeholders) . ')';
        }

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        db()->execute(Sql::INSERT_INTO . $table_name
            . ' (' . implode(', ', array: $all_columns) . ')'
            . ' VALUES ' . implode(', ', array: $value_groups), $params);
    }

    /**
     * Reflect attribute metadata for the using query class.
     *
     * @return array{list<string>, array<string, Persist>, string}
     */
    private static function resolveInsertMeta(): array
    {
        $ReflectionClass = new ReflectionClass(static::class);

        /** @var InsertsInto $InsertsInto */
        $InsertsInto = $ReflectionClass
            ->getAttributes(InsertsInto::class)[0]
            ->newInstance();

        /** @var array<string, Persist> */
        $hooks = [];
        foreach ($ReflectionClass->getAttributes(PersistColumn::class) as $attribute) {
            /** @var PersistColumn $PersistColumn */
            $PersistColumn = $attribute->newInstance();
            $hooks[$PersistColumn->column] = $PersistColumn->Persist;
        }

        $all_columns = $InsertsInto->columns;
        foreach ($hooks as $column => $persist) {
            if (!in_array(needle: $column, haystack: $all_columns, strict: true)) {
                $all_columns[] = $column;
            }
        }

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        return [$all_columns, $hooks, $InsertsInto->table::tableName()];
    }

    /**
     * Extract parameter values from a request object.
     *
     * @param list<string>          $all_columns
     * @param array<string, Persist> $hooks
     * @return array<string, mixed>
     * @throws RandomException
     */
    private static function extractParams(object $request, array $all_columns, array $hooks): array
    {
        $params = [];
        foreach ($all_columns as $column) {
            $value = property_exists(object_or_class: $request, property: $column)
                ? $request->$column
                : null;

            $params[':' . $column] = isset($hooks[$column])
                ? $hooks[$column]->resolve($value)
                : $value;
        }

        return $params;
    }
}
