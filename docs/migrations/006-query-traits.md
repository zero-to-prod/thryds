# Phase 6: Query Traits — Driver-Aware Identifier Quoting

## Goal

Update `DbCreate`, `DbRead`, `DbDelete`, and `DbUpdate` traits to use `Driver::quote()` for identifier quoting instead of hardcoded backticks.

## Current State

The query traits generate standard SQL (`INSERT INTO`, `SELECT`, `DELETE FROM`, `UPDATE`) with named parameter binding. The SQL itself is driver-portable **except** for identifier quoting in `DbRead`:

```php
// DbRead.php line 70 — backtick-quoted ORDER BY
$sql .= self::ORDER_BY . '`' . $SelectsFrom->order_by . '` ASC';

// DbRead.php line 89 — backtick-quoted ORDER BY
$sql .= self::ORDER_BY . '`' . $SelectsFrom->order_by . '` DESC';
```

The other traits (`DbCreate`, `DbDelete`, `DbUpdate`) do not quote identifiers in column lists — they use bare column names in SQL which works across all three drivers. Only `ORDER BY` clauses use backtick quoting.

## Analysis by Trait

### DbCreate

No identifier quoting. Column names in `INSERT INTO table (col1, col2) VALUES (:col1, :col2)` are unquoted. This works on MySQL, PostgreSQL, and SQLite as long as column names are not reserved words.

**Decision:** Add `Driver::quote()` to column names for safety. Reserved word collisions are rare but possible.

### DbRead

Two backtick-quoted locations in ORDER BY clauses.

**Decision:** Replace with `Driver::quote()`.

### DbDelete

No identifier quoting. Column names in `WHERE col = :col` are unquoted.

**Decision:** Add `Driver::quote()` to WHERE column names for consistency.

### DbUpdate

No identifier quoting. Column names in `SET col = :set_col` and `WHERE col = :where_col` are unquoted.

**Decision:** Add `Driver::quote()` to SET and WHERE column names for consistency.

## How Traits Access Driver

The traits use `db()` or accept an optional `Database` parameter. Since `Database` now exposes `driver()` (Phase 3), the traits call:

```php
$Driver = ($Database ?? db())->driver();
```

This is resolved once per method call, not per column.

## Target Changes

### DbCreate

```php
public static function create(object $request, ?Database $Database = null): void
{
    [$all_columns, $hooks, $table_name] = self::resolveInsertMeta();
    $db = $Database ?? db();
    $Driver = $db->driver();

    $db->execute(self::INSERT_INTO . $table_name
        . ' (' . implode(', ', array_map(static fn(string $c) => $Driver->quote($c), $all_columns)) . ')'
        . ' VALUES (' . implode(', ', array_map(
            static fn(string $column): string => ':' . $column,
            $all_columns,
        )) . ')', self::extractParams($request, $all_columns, $hooks));
}
```

Note: `$table_name` comes from `HasTableName::tableName()` which returns the raw table name string. This should also be quoted:

```php
$db->execute(self::INSERT_INTO . $Driver->quote($table_name) . ' ...');
```

### DbRead

```php
// allRows()
if ($SelectsFrom->order_by !== '') {
    $sql .= self::ORDER_BY . $Driver->quote($SelectsFrom->order_by) . ' ASC';
}

// lastRow()
if ($SelectsFrom->order_by !== '') {
    $sql .= self::ORDER_BY . $Driver->quote($SelectsFrom->order_by) . ' DESC';
}

// resolveSelectSql()
$sql = self::SELECT . self::columnList($SelectsFrom, $Driver)
    . self::FROM . $Driver->quote($SelectsFrom->table::tableName());

// WHERE clause
$clauses[] = $Driver->quote($column) . ' = :' . $column;
```

### DbDelete

```php
$clauses[] = $Driver->quote($column) . ' = :' . $column;

return ($database ?? db())->execute(
    self::DELETE_FROM . $Driver->quote($DeletesFrom->table::tableName())
    . self::WHERE . implode(Sql::CONJUNCTION, array: $clauses), $params);
```

### DbUpdate

```php
$set_clauses[] = $Driver->quote($column) . ' = :set_' . $column;
$where_clauses[] = $Driver->quote($column) . ' = :where_' . $column;

return $db->execute(self::UPDATE . $Driver->quote($UpdatesIn->table::tableName())
    . self::SET . implode(', ', array: $set_clauses)
    . self::WHERE . implode(Sql::CONJUNCTION, array: $where_clauses), $params);
```

## Column List Helper

`DbRead::columnList()` returns `*` or a comma-separated list. Add quoting:

```php
private static function columnList(SelectsFrom $SelectsFrom, Driver $Driver): string
{
    return $SelectsFrom->columns !== []
        ? implode(', ', array_map(static fn(string $c) => $Driver->quote($c), $SelectsFrom->columns))
        : '*';
}
```

## Table Name Quoting

All traits reference `$table::tableName()` which returns the raw string value from `TableName` enum. This needs quoting in every trait:

```php
// Before
self::INSERT_INTO . $table_name
self::FROM . $SelectsFrom->table::tableName()

// After
self::INSERT_INTO . $Driver->quote($table_name)
self::FROM . $Driver->quote($SelectsFrom->table::tableName())
```

## Implementation Steps

### Step 1: Update DbRead

- Add `$Driver` resolution via `db()->driver()`
- Replace backtick quoting in ORDER BY with `$Driver->quote()`
- Quote table name and column names in SELECT, WHERE, ORDER BY
- Update `columnList()` signature to accept `Driver`

### Step 2: Update DbCreate

- Add `$Driver` resolution
- Quote table name and column names in INSERT

### Step 3: Update DbDelete

- Add `$Driver` resolution
- Quote table name and column names in DELETE WHERE

### Step 4: Update DbUpdate

- Add `$Driver` resolution
- Quote table name and column names in SET and WHERE

### Step 5: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Queries/DbRead.php` | Quote identifiers via `Driver::quote()` |
| `src/Queries/DbCreate.php` | Quote identifiers via `Driver::quote()` |
| `src/Queries/DbDelete.php` | Quote identifiers via `Driver::quote()` |
| `src/Queries/DbUpdate.php` | Quote identifiers via `Driver::quote()` |
