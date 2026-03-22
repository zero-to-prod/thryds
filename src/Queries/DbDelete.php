<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Attributes\Infrastructure;
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
    public static function delete(mixed ...$where): int
    {
        $database = null;
        if ($where !== [] && end(array: $where) instanceof Database) {
            $database = array_pop(array: $where);
        }

        $DeletesFrom = new ReflectionClass(static::class)
            ->getAttributes(DeletesFrom::class)[0]
            ->newInstance();

        $clauses = [];
        $params = [];

        foreach ($DeletesFrom->where as $index => $column) {
            $clauses[] = $column . ' = :' . $column;
            $params[':' . $column] = $where[$index] ?? null;
        }

        /** @phpstan-ignore method.nonObject (class-string with HasTableName) */
        return ($database ?? db())->execute(Sql::DELETE_FROM . $DeletesFrom->table::tableName()
            . Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
    }
}
