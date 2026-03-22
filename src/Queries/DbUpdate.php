<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;
use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\PersistColumn;
use ZeroToProd\Thryds\Attributes\UpdatesIn;

/**
 * Attribute-driven UPDATE execution.
 *
 * Reads {@see UpdatesIn} and {@see PersistColumn} from the using class,
 * extracts SET values from the request object, applies persistence hooks,
 * and binds WHERE values positionally from trailing arguments.
 */
#[Infrastructure]
trait DbUpdate
{
    private const string UPDATE = 'UPDATE ';

    private const string SET = ' SET ';

    private const string WHERE = ' WHERE ';

    /**
     * UPDATE returning affected row count.
     *
     * The first argument is a request object whose properties map to SET columns.
     * Remaining positional arguments map to WHERE columns declared in {@see UpdatesIn::$where}.
     *
     * @throws RandomException
     */
    public static function update(object $request, mixed ...$where): int
    {
        $ReflectionClass = new ReflectionClass(static::class);

        /** @var UpdatesIn $UpdatesIn */
        $UpdatesIn = $ReflectionClass
            ->getAttributes(UpdatesIn::class)[0]
            ->newInstance();

        /** @var array<string, Persist> $hooks */
        $hooks = [];
        foreach ($ReflectionClass->getAttributes(PersistColumn::class) as $attribute) {
            /** @var PersistColumn $PersistColumn */
            $PersistColumn = $attribute->newInstance();
            $hooks[$PersistColumn->column] = $PersistColumn->Persist;
        }

        $set_clauses = [];
        $params = [];

        foreach ($UpdatesIn->columns as $column) {
            $value = property_exists(object_or_class: $request, property: $column)
                ? $request->$column
                : null;

            $params[':set_' . $column] = isset($hooks[$column])
                ? $hooks[$column]->resolve($value)
                : $value;

            $set_clauses[] = $column . ' = :set_' . $column;
        }

        $where_clauses = [];
        foreach ($UpdatesIn->where as $index => $column) {
            $where_clauses[] = $column . ' = :where_' . $column;
            $params[':where_' . $column] = $where[$index] ?? null;
        }

        return db()->execute(self::UPDATE . $UpdatesIn->table::tableName()
            . self::SET . implode(', ', array: $set_clauses)
            . self::WHERE . implode(Sql::CONJUNCTION, array: $where_clauses), $params);
    }
}
