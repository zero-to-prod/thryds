# UseColumnConstantsInQueriesRector

Replaces magic string column names and table names in Database query calls with Table class constants and `::tableName()` references.

**Category:** Magic String Elimination
**Mode:** `auto` (configurable)
**Auto-fix:** Yes

## Rationale

Constants name things. When column or table names appear as raw strings in queries, renames require string searches across the codebase. By referencing `User::email` and `User::tableName()`, a rename propagates automatically through IDE refactoring or Rector.

## What It Detects

DML statements (INSERT, SELECT, UPDATE, DELETE) passed as string literals to Database method calls (`execute`, `all`, `one`, `scalar`, `insert`) where:

- The SQL string contains a raw table name matching a configured Table class
- Parameter array keys use raw `:column_name` strings instead of `':' . Table::column`
- Column names in the SQL body match public string constants on the resolved Table class

DDL statements (CREATE, ALTER, DROP) are skipped to avoid false positives in column definitions and SQL comments.

## Transformation

### In `auto` mode

1. Table names in SQL are replaced with `TableClass::tableName()`
2. Column names in SQL are replaced with `TableClass::constant`
3. Param placeholders `:col` in SQL become `':' . TableClass::constant` (colon stays in the string literal)
4. Param array keys `':col'` become `':' . TableClass::constant`
5. `ForbidMagicStringArrayKeyRector` TODO comments on transformed keys are removed

## Configuration

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `mode` | `string` | `'auto'` | `'auto'` to transform, `'warn'` to add a TODO comment |
| `tableClasses` | `string[]` | `[]` | Fully-qualified Table class names to resolve columns from |

## Example

### Before

```php
$this->Database->execute(
    'INSERT INTO users (id, email) VALUES (:id, :email)',
    [':id' => $id, ':email' => $email],
);
```

### After

```php
$this->Database->execute(
    'INSERT INTO ' . User::tableName() . ' (' . User::id . ', ' . User::email . ') VALUES (:' . User::id . ', :' . User::email . ')',
    [':' . User::id => $id, ':' . User::email => $email],
);
```

## Related Rules

- `ForbidMagicStringArrayKeyRector` — warns about magic string keys (this rule auto-fixes the Database query subset)
