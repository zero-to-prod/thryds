# Phase 2: DdlBuilder + #[Table] + Migration Actions

## Goal

Make `DdlBuilder` driver-aware, `#[Table]` parameters optional, and migration actions self-resolving. After this phase, all DDL generation works for MySQL, PostgreSQL, and SQLite.

## Part A: DdlBuilder

### Design

`DdlBuilder` is a pure static utility: `(class + Driver) → SQL string`. It accepts `Driver` as a parameter for testability — no container needed to unit test DDL output.

The callers (migration actions) resolve `Driver` themselves from the attribute graph.

### Current MySQL-Specific Code

| Location | Coupling |
|----------|----------|
| Backtick quoting throughout | `'`' . $name . '`'` |
| `createTableSql()` lines 73-75 | `ENGINE=`, `CHARSET=`, `COLLATE=` |
| `columnDdl()` line 94 | `'AUTO_INCREMENT'` |
| `columnDdl()` line 109-111 | `COMMENT '...'` |
| `columnTypeSql()` entire method | MySQL type mapping |
| `ALTER_TABLE` constant | Hardcoded backtick |

### Target Signatures

```php
public static function createTableSql(string $class, Driver $Driver): string
public static function dropTableSql(string $class, Driver $Driver): string
public static function columnDdl(string $name, Column $Column, Driver $Driver): string
public static function addColumnSql(string $class, string $column, Driver $Driver): string
public static function dropColumnSql(string $class, string $column, Driver $Driver): string
public static function reflectForeignKeys(ReflectionClass $ReflectionClass, string $table_name, Driver $Driver): array
```

`columnTypeSql()` is **deleted** — replaced by `Driver::typeSql()`.

`ALTER_TABLE` constant is **deleted** — contained a hardcoded backtick.

Reflection-only methods are **unchanged**: `reflectColumns()`, `reflectPrimaryKey()`, `reflectColumn()`.

### Key Changes in `createTableSql()`

```php
// Quoting
'CREATE TABLE IF NOT EXISTS ' . $Driver->quote($table_name) . " (\n"

// Primary key
'PRIMARY KEY (' . implode(', ', array_map(fn(string $c) => $Driver->quote($c), $pk_columns)) . ')'

// Indexes
'KEY ' . $Driver->quote($index_name) . ' (' . implode(', ', array_map(fn(string $c) => $Driver->quote($c), $Index->columns)) . ')'

// ENUM CHECK constraints (PostgreSQL)
foreach ($php_cols as $prop_name => $col) {
    if ($col->values !== null && $col->values !== []) {
        $check = $Driver->enumConstraint($prop_name, $col->values);
        if ($check !== null) {
            $col_lines[] = self::INDENT . $check;
        }
    }
}

// Table options
')' . $Driver->tableOptions($Table->Engine, $Table->Charset, $Table->Collation)
```

### Key Changes in `columnDdl()`

```php
$parts = [$Driver->quote($name), $Driver->typeSql($Column)];

// AUTO_INCREMENT — MySQL only (PostgreSQL embeds it in type via SERIAL/BIGSERIAL)
if ($Column->auto_increment && $Driver === Driver::mysql) {
    $parts[] = $Driver->autoIncrementSql();
}

// COMMENT — MySQL only
if ($Column->comment !== '' && $Driver === Driver::mysql) {
    $parts[] = "COMMENT '" . addslashes($Column->comment) . "'";
}
```

### Key Changes in `reflectForeignKeys()`

All backticks → `$Driver->quote()`.

### SQLite Guard in `dropColumnSql()`

```php
public static function dropColumnSql(string $class, string $column, Driver $Driver): string
{
    if ($Driver === Driver::sqlite) {
        throw new RuntimeException('SQLite does not support DROP COLUMN. Recreate the table instead.');
    }

    return 'ALTER TABLE ' . $Driver->quote(...) . ' DROP COLUMN ' . $Driver->quote($column);
}
```

## Part B: #[Table] Attribute

### Current

```php
public function __construct(
    public TableName $TableName,
    public Engine $Engine,
    public Charset $Charset,
    public Collation $Collation,
) {}
```

### Target

```php
public function __construct(
    public TableName $TableName,
    public ?Engine $Engine = null,
    public ?Charset $Charset = null,
    public ?Collation $Collation = null,
) {}
```

Existing `#[Table]` declarations with explicit values still work unchanged. PostgreSQL/SQLite tables can omit them:

```php
#[Table(TableName: TableName::users)]  // driver-agnostic
```

When targeting MySQL with null Engine/Charset/Collation, MySQL uses server defaults.

### Enum Docblock Updates

`Engine`, `Charset`, `Collation`, `SortDirection` — update docblocks to note applicability:

- `Engine` — "MySQL/MariaDB only. Ignored by PostgreSQL and SQLite."
- `Charset` — "MySQL/MariaDB only. Ignored by PostgreSQL and SQLite."
- `Collation` — "MySQL/MariaDB only. Ignored by PostgreSQL and SQLite."
- `SortDirection` — remove MySQL-specific note, standard SQL.

## Part C: Migration Actions Self-Resolve Driver

### Design

Migration actions already know their table class (`$this->table`). The table class carries `#[Connection]`. `Connection::resolve()` returns a `Database`. `Database` has `driver()`. The action resolves `Driver` internally — no parameter change to `upSql()` / `downSql()`.

### `CreateTable`

```php
#[MigrationAction]
readonly class CreateTable
{
    public function __construct(public string $table) {}

    public function upSql(): string
    {
        return DdlBuilder::createTableSql($this->table, Connection::resolve($this->table)->driver());
    }

    public function downSql(): string
    {
        return DdlBuilder::dropTableSql($this->table, Connection::resolve($this->table)->driver());
    }
}
```

### `AddColumn`

```php
public function upSql(): string
{
    return DdlBuilder::addColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
}

public function downSql(): string
{
    return DdlBuilder::dropColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
}
```

### `DropColumn`

```php
public function upSql(): string
{
    return DdlBuilder::dropColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
}

public function downSql(): string
{
    return DdlBuilder::addColumnSql($this->table, $this->column, Connection::resolve($this->table)->driver());
}
```

### `RawSql` — Unchanged

```php
public function upSql(): string { return $this->up; }
public function downSql(): string { return $this->down; }
```

No table class, no driver resolution, no signature change. Consumer-authored SQL.

### `#[MigrationAction]` Marker — Unchanged

Duck-type contract stays `upSql(): string` / `downSql(): string`. No parameter added.

### Migrator — Unchanged

```php
$this->Database->execute(self::resolveMigrationAction($class)->upSql());
```

No driver threading. The action resolves it.

## Implementation Steps

1. Add `Driver` param to all `DdlBuilder` SQL-generating methods
2. Replace backticks with `$Driver->quote()` throughout
3. Replace `columnTypeSql()` calls with `$Driver->typeSql()`
4. Replace table suffix with `$Driver->tableOptions()`
5. Add ENUM CHECK constraint support for PostgreSQL
6. Guard `dropColumnSql()` for SQLite
7. Delete `columnTypeSql()` and `ALTER_TABLE` constant
8. Make `Engine`/`Charset`/`Collation` nullable on `#[Table]`
9. Update `CreateTable`/`AddColumn`/`DropColumn` to self-resolve `Driver`
10. Update enum docblocks
11. Run `./run fix:all`

## Files

| File | Action |
|------|--------|
| `src/Schema/DdlBuilder.php` | Refactor — `Driver` param, quoting, type delegation |
| `src/Attributes/Table.php` | Make `Engine`/`Charset`/`Collation` nullable |
| `src/Attributes/CreateTable.php` | Self-resolve `Driver` via `Connection::resolve()` |
| `src/Attributes/AddColumn.php` | Self-resolve `Driver` via `Connection::resolve()` |
| `src/Attributes/DropColumn.php` | Self-resolve `Driver`, SQLite guard |
| `src/Schema/Engine.php` | Docblock |
| `src/Schema/Charset.php` | Docblock |
| `src/Schema/Collation.php` | Docblock |
| `src/Schema/SortDirection.php` | Docblock |
