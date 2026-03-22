<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use ReflectionClass;
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
    private const string DELETE_FROM = 'DELETE FROM ';

    private const string WHERE = ' WHERE ';

    /**
     * DELETE returning affected row count.
     *
     * Positional arguments map to the WHERE columns declared in {@see DeletesFrom::$where}.
     */
    public static function delete(mixed ...$where): int
    {
        $DeletesFrom = new ReflectionClass(static::class)
            ->getAttributes(DeletesFrom::class)[0]
            ->newInstance();

        $clauses = [];
        $params = [];

        foreach ($DeletesFrom->where as $index => $column) {
            $clauses[] = $column . ' = :' . $column;
            $params[':' . $column] = $where[$index] ?? null;
        }

        return db()->execute(self::DELETE_FROM . $DeletesFrom->table::tableName()
            . self::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
    }
}
