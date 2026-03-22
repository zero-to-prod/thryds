<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Connection;
use ZeroToProd\Thryds\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Attributes\Infrastructure;

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

        $clauses = [];
        $params = [];

        foreach ($DeletesFrom->where as $index => $column) {
            $clauses[] = $column . ' = :' . $column;
            $params[':' . $column] = $args[$index] ?? null;
        }

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        return ($args[count($DeletesFrom->where)] ?? Connection::resolve($DeletesFrom->table))->execute(Sql::DELETE_FROM . $DeletesFrom->table::tableName()
            . Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
    }
}
