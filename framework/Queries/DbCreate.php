<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use Random\RandomException;
use ReflectionClass;
use ZeroToProd\Framework\Attributes\Connection;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\InsertsInto;
use ZeroToProd\Framework\Attributes\PersistColumn;
use ZeroToProd\Framework\Attributes\Table;
use ZeroToProd\Framework\Database;

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
        [$all_columns, $hooks, $table_name, $table] = self::resolveInsertMeta();
        $db = $Database ?? Connection::resolve(class: $table);
        $Driver = $db->driver();

        $db->execute(Sql::INSERT_INTO . $Driver->quote(identifier: $table_name)
            . ' (' . implode(', ', array_map(static fn(string $c): string => $Driver->quote(identifier: $c), $all_columns)) . ')'
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

        [$all_columns, $hooks, $table_name, $table] = self::resolveInsertMeta();

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

        $Database = Connection::resolve(class: $table);
        $Driver = $Database->driver();

        $Database->execute(Sql::INSERT_INTO . $Driver->quote(identifier: $table_name)
            . ' (' . implode(', ', array_map(static fn(string $c): string => $Driver->quote(identifier: $c), $all_columns)) . ')'
            . ' VALUES ' . implode(', ', array: $value_groups), $params);
    }

    /**
     * Reflect attribute metadata for the using query class.
     *
     * @return array{list<string>, array<string, Persist>, string, class-string}
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

        return [$all_columns, $hooks, new ReflectionClass($InsertsInto->table)
            ->getAttributes(Table::class)[0]
            ->newInstance()
            ->TableName->value, $InsertsInto->table];
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
