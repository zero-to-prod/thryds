<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries;

use ReflectionClass;
use ZeroToProd\Framework\Attributes\Connection;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Attributes\SelectsFrom;
use ZeroToProd\Framework\Attributes\Table;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\Schema\Driver;

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
        $db = $Database ?? Connection::resolve($SelectsFrom->table);
        $Driver = $db->driver();

        $sql = Sql::SELECT . self::columnList($SelectsFrom, $Driver)
            . Sql::FROM . $Driver->quote(self::resolveTableName($SelectsFrom));
        $sql .= self::orderByClause($SelectsFrom, $Driver);
        $sql .= self::limitOffsetClause($SelectsFrom);

        return $db->all($sql);
    }

    /**
     * SELECT returning one row with ordering, limit, and offset from the attribute.
     *
     * @return array<string, mixed>|null
     */
    public static function oneRow(?Database $Database = null): ?array
    {
        $SelectsFrom = self::resolveSelectsFrom();
        $db = $Database ?? Connection::resolve($SelectsFrom->table);
        $Driver = $db->driver();

        $sql = Sql::SELECT . self::columnList($SelectsFrom, $Driver)
            . Sql::FROM . $Driver->quote(self::resolveTableName($SelectsFrom));
        $sql .= self::orderByClause($SelectsFrom, $Driver);
        $sql .= self::limitOffsetClause($SelectsFrom);

        return $db->one($sql);
    }

    /**
     * Build the SELECT SQL, parameter array, and optional Database from positional args.
     *
     * WHERE values occupy indices 0..N-1 (matching {@see SelectsFrom::$where}).
     * An optional trailing Database instance at index N overrides the default connection.
     *
     * @param array<int|string, mixed> $args
     * @return array{string, array<string, mixed>, ?Database, class-string}
     */
    private static function resolveSelectSqlWithConnection(array $args): array
    {
        $SelectsFrom = self::resolveSelectsFrom();

        $raw = $args[count($SelectsFrom->where)] ?? null;
        $database = $raw instanceof Database ? $raw : null;
        $Driver = ($database ?? Connection::resolve($SelectsFrom->table))->driver();

        $sql = Sql::SELECT . self::columnList($SelectsFrom, $Driver)
            . Sql::FROM . $Driver->quote(self::resolveTableName($SelectsFrom));

        $params = [];

        if ($SelectsFrom->where !== []) {
            $clauses = [];
            foreach ($SelectsFrom->where as $index => $column) {
                $clauses[] = $Driver->quote(identifier: $column) . ' = :' . $column;
                $params[':' . $column] = $args[$index] ?? null;
            }
            $sql .= Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses);
        }

        return [$sql, $params, $database, $SelectsFrom->table];
    }

    private static function columnList(SelectsFrom $SelectsFrom, Driver $Driver): string
    {
        return $SelectsFrom->columns !== []
            ? implode(', ', array_map(static fn(string $c): string => $Driver->quote(identifier: $c), $SelectsFrom->columns))
            : '*';
    }

    private static function orderByClause(SelectsFrom $SelectsFrom, Driver $Driver): string
    {
        if ($SelectsFrom->order_by === '') {
            return '';
        }

        return Sql::ORDER_BY . $Driver->quote($SelectsFrom->order_by) . ' ' . $SelectsFrom->SortDirection->value;
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

    private static function resolveTableName(SelectsFrom $SelectsFrom): string
    {
        return new ReflectionClass($SelectsFrom->table)
            ->getAttributes(Table::class)[0]
            ->newInstance()
            ->TableName->value;
    }
}
