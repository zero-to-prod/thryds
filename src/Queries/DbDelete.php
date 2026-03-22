<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Connection;
use ZeroToProd\Thryds\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Database;

/**
 * Attribute-driven DELETE execution.
 *
 * Reads {@see DeletesFrom} from the using class, builds the DELETE statement,
 * and binds WHERE values positionally from method arguments.
 */
#[Infrastructure]
trait DbDelete
{
    /**
     * DELETE returning affected row count.
     *
     * Positional arguments map to the WHERE columns declared in {@see DeletesFrom::$where}.
     * An optional trailing Database instance overrides the default connection.
     */
    public static function delete(mixed ...$args): int
    {
        $DeletesFrom = new ReflectionClass(static::class)
            ->getAttributes(DeletesFrom::class)[0]
            ->newInstance();

        $db = $args[count($DeletesFrom->where)] ?? null;
        $resolvedDb = $db instanceof Database ? $db : Connection::resolve($DeletesFrom->table);
        $Driver = $resolvedDb->driver();

        $clauses = [];
        $params = [];

        foreach ($DeletesFrom->where as $index => $column) {
            $clauses[] = $Driver->quote(identifier: $column) . ' = :' . $column;
            $params[':' . $column] = $args[$index] ?? null;
        }

        return $resolvedDb->execute(Sql::DELETE_FROM . $Driver->quote(new ReflectionClass($DeletesFrom->table)
            ->getAttributes(Table::class)[0]
            ->newInstance()
            ->TableName->value)
            . Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
    }
}
