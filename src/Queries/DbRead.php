<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\SelectsFrom;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\Schema\SortDirection;

/**
 * Attribute-driven SELECT execution.
 *
 * Reads {@see SelectsFrom} from the using class, builds the SELECT statement,
 * and binds WHERE values positionally from method arguments.
 */
#[Infrastructure]
trait DbRead
{
    /**
     * SELECT returning one row or null.
     *
     * Positional arguments map to the WHERE columns declared in {@see SelectsFrom::$where}.
     *
     * @return array<string, mixed>|null
     */
    public static function one(mixed ...$where): ?array
    {
        [$sql, $params] = self::resolveSelectSql(where_values: $where);

        return db()->one($sql, $params);
    }

    /**
     * SELECT returning all matching rows.
     *
     * Positional arguments map to the WHERE columns declared in {@see SelectsFrom::$where}.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(mixed ...$where): array
    {
        [$sql, $params] = self::resolveSelectSql(where_values: $where);

        return db()->all($sql, $params);
    }

    /**
     * SELECT returning all rows ordered by the declared orderBy column.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allRows(?Database $Database = null): array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        $sql = Sql::SELECT . self::columnList($SelectsFrom)
            . Sql::FROM . $SelectsFrom->table::tableName();
        if ($SelectsFrom->order_by !== '') {
            $sql .= Sql::ORDER_BY . '`' . $SelectsFrom->order_by . '` ' . $SelectsFrom->SortDirection->value;
        }

        return ($Database ?? db())->all($sql);
    }

    /**
     * SELECT returning the last row by the declared orderBy column, or null.
     *
     * @return array<string, mixed>|null
     */
    public static function lastRow(?Database $Database = null): ?array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        $sql = Sql::SELECT . self::columnList($SelectsFrom)
            . Sql::FROM . $SelectsFrom->table::tableName();
        if ($SelectsFrom->order_by !== '') {
            $sql .= Sql::ORDER_BY . '`' . $SelectsFrom->order_by . '` ' . ($SelectsFrom->SortDirection === SortDirection::ASC
                ? SortDirection::DESC
                : SortDirection::ASC)->value;
        }
        $sql .= ' LIMIT 1';

        return ($Database ?? db())->one($sql);
    }

    /**
     * Build the SELECT SQL and parameter array from attributes and positional WHERE values.
     *
     * @param array<int|string, mixed> $where_values
     * @return array{string, array<string, mixed>}
     */
    private static function resolveSelectSql(array $where_values): array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        $sql = Sql::SELECT . self::columnList($SelectsFrom)
            . Sql::FROM . $SelectsFrom->table::tableName();

        $params = [];

        if ($SelectsFrom->where !== []) {
            $clauses = [];
            foreach ($SelectsFrom->where as $index => $column) {
                $clauses[] = $column . ' = :' . $column;
                $params[':' . $column] = $where_values[$index] ?? null;
            }
            $sql .= Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses);
        }

        return [$sql, $params];
    }

    private static function columnList(SelectsFrom $SelectsFrom): string
    {
        return $SelectsFrom->columns !== [] ? implode(', ', $SelectsFrom->columns) : '*';
    }

    private static function resolveSelectsFrom(): SelectsFrom
    {
        return new ReflectionClass(static::class)
            ->getAttributes(SelectsFrom::class)[0]
            ->newInstance();
    }
}
