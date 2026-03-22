# Phase 3: Query Traits — Driver-Aware Identifier Quoting

## Goal

Add `Driver::quote()` to all four query traits. The traits already resolve `Database` via `Connection::resolve()` — adding `driver()` completes the chain with no new coupling.

## Resolution Chain

```
DbCreate::create()
  → Connection::resolve($table) → Database → driver() → Driver
  → $Driver->quote($column)
```

`Driver` is resolved once per method call from the `Database` instance the trait already has.

## Current MySQL-Specific Code

Only one explicit backtick in the traits:

```php
// DbRead::orderByClause() line 132
return Sql::ORDER_BY . '`' . $SelectsFrom->order_by . '` ' . $SelectsFrom->SortDirection->value;
```

All other column and table references are unquoted bare names. Quoting them all is defensive against reserved word collisions.

## Changes Per Trait

### DbCreate

```php
public static function create(object $request, ?Database $Database = null): void
{
    [$all_columns, $hooks, $table_name, $table] = self::resolveInsertMeta();
    $db = $Database ?? Connection::resolve(class: $table);
    $Driver = $db->driver();

    $db->execute(Sql::INSERT_INTO . $Driver->quote($table_name)
        . ' (' . implode(', ', array_map(static fn(string $c) => $Driver->quote($c), $all_columns)) . ')'
        . ' VALUES (' . implode(', ', array_map(
            static fn(string $column): string => ':' . $column,
            $all_columns,
        )) . ')', self::extractParams($request, $all_columns, $hooks));
}
```

Same pattern for `createMany()`.

### DbRead

```php
// resolveSelectSqlWithConnection() — quote table, columns, WHERE
$db = $args[count($SelectsFrom->where)] ?? null;
$resolvedDb = $db instanceof Database ? $db : Connection::resolve($SelectsFrom->table);
$Driver = $resolvedDb->driver();

$sql = Sql::SELECT . self::columnList($SelectsFrom, $Driver)
    . Sql::FROM . $Driver->quote($SelectsFrom->table::tableName());

$clauses[] = $Driver->quote($column) . ' = :' . $column;

// columnList() — add Driver param
private static function columnList(SelectsFrom $SelectsFrom, Driver $Driver): string
{
    return $SelectsFrom->columns !== []
        ? implode(', ', array_map(static fn(string $c) => $Driver->quote($c), $SelectsFrom->columns))
        : '*';
}

// orderByClause() — replace backtick
private static function orderByClause(SelectsFrom $SelectsFrom, Driver $Driver): string
{
    if ($SelectsFrom->order_by === '') {
        return '';
    }

    return Sql::ORDER_BY . $Driver->quote($SelectsFrom->order_by) . ' ' . $SelectsFrom->SortDirection->value;
}
```

`allRows()` and `oneRow()` resolve `$Driver` from `$Database ?? Connection::resolve($SelectsFrom->table)`.

`limitOffsetClause()` is **unchanged** — `LIMIT` and `OFFSET` are standard SQL.

### DbDelete

```php
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
        $clauses[] = $Driver->quote($column) . ' = :' . $column;
        $params[':' . $column] = $args[$index] ?? null;
    }

    return $resolvedDb->execute(Sql::DELETE_FROM . $Driver->quote($DeletesFrom->table::tableName())
        . Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
}
```

### DbUpdate

```php
public static function update(object $request, mixed ...$where): int
{
    [$columns, $hooks, $where_columns, $table_name, $table] = self::resolveUpdateMeta();
    $db = Connection::resolve(class: $table);
    $Driver = $db->driver();

    // ...
    $set_clauses[] = $Driver->quote($column) . ' = :set_' . $column;
    $where_clauses[] = $Driver->quote($column) . ' = :where_' . $column;

    return $db->execute(Sql::UPDATE . $Driver->quote($table_name)
        . Sql::SET . implode(', ', array: $set_clauses)
        . Sql::WHERE . implode(Sql::CONJUNCTION, array: $where_clauses), $params);
}
```

## Implementation Steps

1. Update `DbRead` — resolve `$Driver`, quote ORDER BY, table, columns, WHERE; add `Driver` param to `columnList()` and `orderByClause()`
2. Update `DbCreate` — resolve `$Driver`, quote table and columns in INSERT
3. Update `DbDelete` — resolve `$Driver`, quote table and columns in DELETE WHERE
4. Update `DbUpdate` — resolve `$Driver`, quote table and columns in SET and WHERE
5. Run `./run fix:all`

## Files

| File | Change |
|------|--------|
| `src/Queries/DbRead.php` | Quote identifiers, update `columnList()` and `orderByClause()` signatures |
| `src/Queries/DbCreate.php` | Quote identifiers |
| `src/Queries/DbDelete.php` | Quote identifiers |
| `src/Queries/DbUpdate.php` | Quote identifiers |
