# Phase 4: DdlBuilder — Driver-Delegated DDL

## Goal

Refactor `DdlBuilder` so every SQL generation method accepts a `Driver` parameter and delegates quoting, type mapping, and table options to `Driver` methods. No MySQL-specific SQL remains in `DdlBuilder`.

## Current MySQL-Specific Code

| Location | MySQL Coupling |
|----------|---------------|
| `createTableSql()` line 59 | `` '`' . $c . '`' `` — backtick quoting |
| `createTableSql()` line 64 | `` '`' `` — backtick quoting in index names |
| `createTableSql()` line 71 | `` 'CREATE TABLE IF NOT EXISTS `' `` — backticks |
| `createTableSql()` lines 73-75 | `ENGINE=`, `CHARSET=`, `COLLATE=` suffixes |
| `columnDdl()` line 90 | `` '`' . $name . '`' `` — backtick quoting |
| `columnDdl()` line 94 | `'AUTO_INCREMENT'` keyword |
| `columnTypeSql()` lines 118-145 | Entire method is MySQL type mapping |
| `addColumnSql()` line 206 | Backtick quoting |
| `dropColumnSql()` line 217 | Backtick quoting |
| `reflectForeignKeys()` lines 268-272 | Backtick quoting in CONSTRAINT clause |
| `ALTER_TABLE` constant line 30 | `` 'ALTER TABLE `' `` — hardcoded backtick |

## Target Signatures

Every public method that generates SQL gains a `Driver` parameter:

```php
public static function createTableSql(string $class, Driver $Driver): string
public static function dropTableSql(string $class, Driver $Driver): string
public static function columnDdl(string $name, Column $Column, Driver $Driver): string
public static function addColumnSql(string $class, string $column, Driver $Driver): string
public static function dropColumnSql(string $class, string $column, Driver $Driver): string
public static function reflectForeignKeys(ReflectionClass $ReflectionClass, string $table_name, Driver $Driver): array
```

`columnTypeSql()` is deleted — its logic moves to `Driver::typeSql()`.

The `ALTER_TABLE` constant is removed — it contains a hardcoded backtick.

Reflection-only methods are unchanged:
```php
public static function reflectColumns(ReflectionClass $ReflectionClass): array     // unchanged
public static function reflectPrimaryKey(ReflectionClass $ReflectionClass): array  // unchanged
public static function reflectColumn(ReflectionClass $ReflectionClass, string $column): Column  // unchanged
```

## Target Implementation

### `createTableSql()`

```php
public static function createTableSql(string $class, Driver $Driver): string
{
    $ReflectionClass = new ReflectionClass(objectOrClass: $class);
    $Table = $ReflectionClass->getAttributes(Table::class)[0]->newInstance();
    $table_name = $Table->TableName->value;

    $php_cols = self::reflectColumns($ReflectionClass);

    $col_lines = [];
    foreach ($php_cols as $prop_name => $col) {
        $col_lines[] = self::INDENT . self::columnDdl(name: $prop_name, Column: $col, Driver: $Driver);
    }

    $pk_columns = self::reflectPrimaryKey($ReflectionClass);
    if ($pk_columns !== []) {
        $col_lines[] = self::INDENT . 'PRIMARY KEY (' . implode(', ', array_map(
            static fn(string $c): string => $Driver->quote($c), $pk_columns
        )) . ')';
    }

    foreach ($ReflectionClass->getAttributes(Index::class) as $idx_attr) {
        $Index = $idx_attr->newInstance();
        $col_lines[] = self::INDENT . ($Index->unique ? 'UNIQUE ' : '')
            . 'KEY ' . $Driver->quote($Index->name !== '' ? $Index->name : 'idx_' . $table_name . '_' . implode('_', $Index->columns))
            . ' (' . implode(', ', array_map(static fn(string $c): string => $Driver->quote($c), $Index->columns)) . ')';
    }

    // ENUM CHECK constraints (PostgreSQL)
    foreach ($php_cols as $prop_name => $col) {
        if ($col->values !== null && $col->values !== []) {
            $check = $Driver->enumConstraint($prop_name, $col->values);
            if ($check !== null) {
                $col_lines[] = self::INDENT . $check;
            }
        }
    }

    foreach (self::reflectForeignKeys($ReflectionClass, $table_name, $Driver) as $fk_clause) {
        $col_lines[] = self::INDENT . $fk_clause;
    }

    return 'CREATE TABLE IF NOT EXISTS ' . $Driver->quote($table_name) . " (\n"
        . implode(",\n", array: $col_lines) . "\n"
        . ')' . $Driver->tableOptions($Table->Engine, $Table->Charset, $Table->Collation);
}
```

### `columnDdl()`

```php
public static function columnDdl(string $name, Column $Column, Driver $Driver): string
{
    $parts = [$Driver->quote($name), $Driver->typeSql($Column)];
    $parts[] = $Column->nullable ? 'NULL' : 'NOT NULL';

    if ($Column->auto_increment && $Driver === Driver::mysql) {
        $parts[] = $Driver->autoIncrementSql();
    }
    // PostgreSQL: auto-increment is embedded in type (SERIAL/BIGSERIAL)
    // SQLite: AUTOINCREMENT only valid on INTEGER PRIMARY KEY (handled separately)

    if ($Column->default !== null) {
        if ($Column->default === Column::CURRENT_TIMESTAMP) {
            $parts[] = self::DEFAULT . 'CURRENT_TIMESTAMP';
        } elseif (is_bool($Column->default)) {
            $parts[] = self::DEFAULT . ($Column->default ? '1' : '0');
        } elseif (is_int($Column->default) || is_float($Column->default)) {
            $parts[] = self::DEFAULT . $Column->default;
        } else {
            $parts[] = self::DEFAULT . "'" . addslashes((string) $Column->default) . "'";
        }
    }

    if ($Column->comment !== '' && $Driver === Driver::mysql) {
        $parts[] = "COMMENT '" . addslashes($Column->comment) . "'";
    }
    // PostgreSQL and SQLite do not support inline COMMENT on columns

    return implode(' ', array: $parts);
}
```

### `reflectForeignKeys()`

```php
public static function reflectForeignKeys(ReflectionClass $ReflectionClass, string $table_name, Driver $Driver): array
{
    $clauses = [];

    foreach ($ReflectionClass->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC) as $const) {
        $fk_attrs = $const->getAttributes(ForeignKey::class);
        if ($fk_attrs === []) {
            continue;
        }

        $ForeignKey = $fk_attrs[0]->newInstance();
        $column = (string) $const->getValue();
        $target_table = new ReflectionClass($ForeignKey->BackedEnum)
            ->getAttributes(Table::class)[0]
            ->newInstance()
            ->TableName->value;
        $target_column = (string) $ForeignKey->BackedEnum->value;

        $on_delete_attrs = $const->getAttributes(OnDelete::class);
        $on_update_attrs = $const->getAttributes(OnUpdate::class);

        $clauses[] = 'CONSTRAINT ' . $Driver->quote($ForeignKey->name !== '' ? $ForeignKey->name : 'fk_' . $table_name . '_' . $column . '_' . $target_table)
            . ' FOREIGN KEY (' . $Driver->quote($column) . ')'
            . ' REFERENCES ' . $Driver->quote($target_table) . ' (' . $Driver->quote($target_column) . ')'
            . ' ON DELETE ' . ($on_delete_attrs !== [] ? $on_delete_attrs[0]->newInstance()->ReferentialAction : ReferentialAction::RESTRICT)->value
            . ' ON UPDATE ' . ($on_update_attrs !== [] ? $on_update_attrs[0]->newInstance()->ReferentialAction : ReferentialAction::RESTRICT)->value;
    }

    return $clauses;
}
```

## Callers to Update

### `CreateTable` attribute

```php
// Current — upSql()/downSql() take no args (duck-typed from #[MigrationAction] marker)
public function upSql(): string
{
    return DdlBuilder::createTableSql($this->table);
}

// After — accepts Driver parameter
public function upSql(Driver $Driver): string
{
    return DdlBuilder::createTableSql($this->table, $Driver);
}

public function downSql(Driver $Driver): string
{
    return DdlBuilder::dropTableSql($this->table, $Driver);
}
```

### `AddColumn` attribute

```php
public function upSql(Driver $Driver): string
{
    return DdlBuilder::addColumnSql($this->table, $this->column, $Driver);
}

public function downSql(Driver $Driver): string
{
    return DdlBuilder::dropColumnSql($this->table, $this->column, $Driver);
}
```

### `DropColumn` attribute

```php
public function upSql(Driver $Driver): string
{
    return DdlBuilder::dropColumnSql($this->table, $this->column, $Driver);
}

public function downSql(Driver $Driver): string
{
    return DdlBuilder::addColumnSql($this->table, $this->column, $Driver);
}
```

### `RawSql` attribute — No change

`RawSql::upSql()` and `downSql()` return consumer-authored SQL strings. They gain the `Driver` parameter for interface consistency but ignore it:

```php
public function upSql(Driver $Driver): string
{
    return $this->up;
}

public function downSql(Driver $Driver): string
{
    return $this->down;
}
```

Note: `#[RawSql]` migrations are inherently driver-specific. The consumer is responsible for writing driver-appropriate SQL. The docblock should note this.

### `#[MigrationAction]` marker — duck-type contract update

The marker attribute's docblock is updated to reflect the new signature:

```php
/**
 * Marks an attribute class as a migration action that provides DDL operations.
 *
 * Classes carrying this attribute must declare:
 *   upSql(Driver $Driver): string
 *   downSql(Driver $Driver): string
 */
#[Attribute(Attribute::TARGET_CLASS)]
readonly class MigrationAction {}
```

### `Migrator` (preview of Phase 7)

```php
// runUp() — passes driver to migration action
$this->Database->execute(self::resolveMigrationAction($class)->upSql($this->Database->driver()));
```

## SQLite-Specific: ALTER TABLE Limitations

SQLite before 3.35.0 does not support `DROP COLUMN`. `dropColumnSql()` should guard:

```php
public static function dropColumnSql(string $class, string $column, Driver $Driver): string
{
    if ($Driver === Driver::sqlite) {
        throw new RuntimeException('SQLite does not support DROP COLUMN. Recreate the table instead.');
    }

    return 'ALTER TABLE ' . $Driver->quote(
        new ReflectionClass($class)->getAttributes(Table::class)[0]->newInstance()->TableName->value
    ) . ' DROP COLUMN ' . $Driver->quote($column);
}
```

## Implementation Steps

### Step 1: Update `#[MigrationAction]` marker docblock

- Document `upSql(Driver $Driver): string` and `downSql(Driver $Driver): string`

### Step 2: Update `CreateTable`, `AddColumn`, `DropColumn`, `RawSql`

- Add `Driver` parameter to `upSql()` and `downSql()`
- `CreateTable`/`AddColumn`/`DropColumn` pass `Driver` to `DdlBuilder`
- `RawSql` accepts but ignores `Driver`

### Step 3: Refactor `DdlBuilder` public methods

- Add `Driver` parameter to all SQL-generating methods
- Replace all backtick literals with `$Driver->quote()`
- Replace `self::columnTypeSql()` calls with `$Driver->typeSql()`
- Replace `ENGINE=`/`CHARSET=`/`COLLATE=` with `$Driver->tableOptions()`
- Replace `'AUTO_INCREMENT'` with `$Driver->autoIncrementSql()` (MySQL only)
- Add `Driver::enumConstraint()` calls for PostgreSQL ENUM emulation
- Guard `dropColumnSql()` for SQLite
- Delete `ALTER_TABLE` constant (contained hardcoded backtick)

### Step 4: Delete `columnTypeSql()` from `DdlBuilder`

- Logic now lives in `Driver::typeSql()`

### Step 5: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Schema/DdlBuilder.php` | All SQL methods accept `Driver`, delegate quoting and types |
| `src/Attributes/MigrationAction.php` | Docblock updated for `upSql(Driver)`/`downSql(Driver)` contract |
| `src/Attributes/CreateTable.php` | `upSql(Driver)`, `downSql(Driver)` |
| `src/Attributes/AddColumn.php` | `upSql(Driver)`, `downSql(Driver)` |
| `src/Attributes/DropColumn.php` | `upSql(Driver)`, `downSql(Driver)`, SQLite guard |
| `src/Attributes/RawSql.php` | `upSql(Driver)`, `downSql(Driver)` — param accepted, ignored |
