# Phase 6: Query Traits — Driver-Aware Identifier Quoting

## Goal

Update `DbCreate`, `DbRead`, `DbDelete`, and `DbUpdate` traits to use `Driver::quote()` for identifier quoting instead of hardcoded backticks.

## Current State

The query traits now use `Sql::*` constants for keywords and resolve connections via `Connection::resolve()`. The only remaining MySQL-specific code is backtick quoting in `DbRead::orderByClause()`:

```php
// DbRead.php line 132 — backtick-quoted ORDER BY
return Sql::ORDER_BY . '`' . $SelectsFrom->order_by . '` ' . $SelectsFrom->SortDirection->value;
```

All other column and table references are unquoted bare names in SQL (which works across drivers for non-reserved names).

## Analysis by Trait

### DbCreate

No identifier quoting. Uses `Sql::INSERT_INTO`. Column names in `INSERT INTO table (col1, col2) VALUES (:col1, :col2)` are unquoted. Table name from `HasTableName::tableName()` is unquoted.

**Decision:** Add `Driver::quote()` for safety against reserved word collisions.

### DbRead

One backtick location in `orderByClause()`. `resolveSelectSqlWithConnection()` leaves column and table names unquoted. `columnList()` returns bare column names or `*`.

**Decision:** Replace backtick with `Driver::quote()`. Add quoting to table name, WHERE columns, and column list.

### DbDelete

No identifier quoting. Uses `Sql::DELETE_FROM`. Column names in `WHERE col = :col` are unquoted.

**Decision:** Add `Driver::quote()` for consistency.

### DbUpdate

No identifier quoting. Uses `Sql::UPDATE`, `Sql::SET`, `Sql::WHERE`. Column names in `SET col = :set_col` and `WHERE col = :where_col` are unquoted.

**Decision:** Add `Driver::quote()` for consistency.

## How Traits Access Driver

The traits already resolve `Database` via `Connection::resolve($table_class)`. Since `Database` now exposes `driver()` (Phase 3), the chain is:

```
Query trait → Connection::resolve($table) → Database → driver() → Driver
```

For methods that accept an explicit `?Database` parameter, the driver is resolved from that instance:

```php
$db = $Database ?? Connection::resolve($table);
$Driver = $db->driver();
```

This is resolved once per method call, not per column.

## Target Changes

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
// resolveSelectSqlWithConnection()
$db = $args[count($SelectsFrom->where)] ?? null;
$resolvedDb = $db instanceof Database ? $db : Connection::resolve($SelectsFrom->table);
$Driver = $resolvedDb->driver();

$sql = Sql::SELECT . self::columnList($SelectsFrom, $Driver)
    . Sql::FROM . $Driver->quote($SelectsFrom->table::tableName());

// WHERE clause
$clauses[] = $Driver->quote($column) . ' = :' . $column;

// orderByClause()
return Sql::ORDER_BY . $Driver->quote($SelectsFrom->order_by) . ' ' . $SelectsFrom->SortDirection->value;

// columnList() — add Driver parameter
private static function columnList(SelectsFrom $SelectsFrom, Driver $Driver): string
{
    return $SelectsFrom->columns !== []
        ? implode(', ', array_map(static fn(string $c) => $Driver->quote($c), $SelectsFrom->columns))
        : '*';
}
```

`allRows()` and `oneRow()` follow the same pattern — resolve `$Driver` from the database instance.

### DbDelete

```php
$db = $args[count($DeletesFrom->where)] ?? null;
$resolvedDb = $db instanceof Database ? $db : Connection::resolve($DeletesFrom->table);
$Driver = $resolvedDb->driver();

$clauses[] = $Driver->quote($column) . ' = :' . $column;

return $resolvedDb->execute(
    Sql::DELETE_FROM . $Driver->quote($DeletesFrom->table::tableName())
    . Sql::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
```

### DbUpdate

```php
$db = Connection::resolve(class: $table);
$Driver = $db->driver();

$set_clauses[] = $Driver->quote($column) . ' = :set_' . $column;
$where_clauses[] = $Driver->quote($column) . ' = :where_' . $column;

return $db->execute(Sql::UPDATE . $Driver->quote($table_name)
    . Sql::SET . implode(', ', array: $set_clauses)
    . Sql::WHERE . implode(Sql::CONJUNCTION, array: $where_clauses), $params);
```

## `#[Connection]` Resolution Chain

The `Connection::resolve()` method reads `#[Connection]` from the table class and resolves a `Database` from the container. This already works per-table. Adding `driver()` to `Database` means each table can theoretically target a different driver (e.g., user data in PostgreSQL, cache in SQLite) — though this is a future capability, not a current requirement.

```
#[Connection(database: Database::class)]   → resolves Database → driver() → Driver::mysql
#[Connection(database: ReadReplica::class)] → resolves ReadReplica → driver() → Driver::pgsql
```

## Implementation Steps

### Step 1: Update DbRead

- Resolve `$Driver` via `Connection::resolve()->driver()` or explicit `$Database->driver()`
- Replace backtick quoting in `orderByClause()` with `$Driver->quote()`
- Quote table name and column names in SELECT, WHERE, ORDER BY
- Update `columnList()` signature to accept `Driver`

### Step 2: Update DbCreate

- Resolve `$Driver` from resolved `Database`
- Quote table name and column names in INSERT

### Step 3: Update DbDelete

- Resolve `$Driver` from resolved `Database`
- Quote table name and column names in DELETE WHERE

### Step 4: Update DbUpdate

- Resolve `$Driver` from resolved `Database`
- Quote table name and column names in SET and WHERE

### Step 5: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Queries/DbRead.php` | Quote identifiers via `Driver::quote()`, update `columnList()` and `orderByClause()` |
| `src/Queries/DbCreate.php` | Quote identifiers via `Driver::quote()` |
| `src/Queries/DbDelete.php` | Quote identifiers via `Driver::quote()` |
| `src/Queries/DbUpdate.php` | Quote identifiers via `Driver::quote()` |
