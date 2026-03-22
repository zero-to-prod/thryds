<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Connection;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\SelectsFrom;
use ZeroToProd\Thryds\Database;

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
     * An optional trailing Database instance overrides the default connection.
     *
     * @return array<string, mixed>|null
     */
    public static function one(mixed ...$args): ?array
    {
        [$sql, $params, $database, $table] = self::resolveSelectSqlWithConnection($args);

        /** @phpstan-ignore method.nonObject (Database|null from trailing arg) */
        return ($database ?? Connection::resolve(class: $table))->one($sql, $params);
    }

    /**
     * SELECT returning all matching rows.
     *
     * Positional arguments map to the WHERE columns declared in {@see SelectsFrom::$where}.
     * An optional trailing Database instance overrides the default connection.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function all(mixed ...$args): array
    {
        [$sql, $params, $database, $table] = self::resolveSelectSqlWithConnection($args);

        /** @phpstan-ignore method.nonObject (Database|null from trailing arg) */
        return ($database ?? Connection::resolve(class: $table))->all($sql, $params);
    }

    /**
     * SELECT returning all rows with ordering, limit, and offset from the attribute.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function allRows(?Database $Database = null): array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        $sql = Sql::SELECT . self::columnList($SelectsFrom)
            . Sql::FROM . $SelectsFrom->table::tableName();
        $sql .= self::orderByClause($SelectsFrom);
        $sql .= self::limitOffsetClause($SelectsFrom);

        return ($Database ?? Connection::resolve($SelectsFrom->table))->all($sql);
    }

    /**
     * SELECT returning one row with ordering, limit, and offset from the attribute.
     *
     * @return array<string, mixed>|null
     */
    public static function oneRow(?Database $Database = null): ?array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        $sql = Sql::SELECT . self::columnList($SelectsFrom)
            . Sql::FROM . $SelectsFrom->table::tableName();
        $sql .= self::orderByClause($SelectsFrom);
        $sql .= self::limitOffsetClause($SelectsFrom);

        return ($Database ?? Connection::resolve($SelectsFrom->table))->one($sql);
    }

    /**
     * Build the SELECT SQL, parameter array, and optional Database from positional args.
     *
     * WHERE values occupy indices 0..N-1 (matching {@see SelectsFrom::$where}).
     * An optional trailing Database instance at index N overrides the default connection.
     *
     * @param array<int|string, mixed> $args
     * @return array{string, array<string, mixed>, mixed, class-string}
     */
    private static function resolveSelectSqlWithConnection(array $args): array
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
                $params[':' . $column] = $args[$index] ?? null;
            }
            $sql .= Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses);
        }

        return [$sql, $params, $args[count($SelectsFrom->where)] ?? null, $SelectsFrom->table];
    }

    private static function columnList(SelectsFrom $SelectsFrom): string
    {
        return $SelectsFrom->columns !== [] ? implode(', ', $SelectsFrom->columns) : '*';
    }

    private static function orderByClause(SelectsFrom $SelectsFrom): string
    {
        if ($SelectsFrom->order_by === '') {
            return '';
        }

        return Sql::ORDER_BY . '`' . $SelectsFrom->order_by . '` ' . $SelectsFrom->SortDirection->value;
    }

    private static function limitOffsetClause(SelectsFrom $SelectsFrom): string
    {
        $sql = '';

        if ($SelectsFrom->limit !== null) {
            $sql .= ' LIMIT ' . $SelectsFrom->limit;
        }

        if ($SelectsFrom->offset !== null) {
            $sql .= ' OFFSET ' . $SelectsFrom->offset;
        }

        return $sql;
    }

    private static function resolveSelectsFrom(): SelectsFrom
    {
        return new ReflectionClass(static::class)
            ->getAttributes(SelectsFrom::class)[0]
            ->newInstance();
    }
}
