# Phase 1: Driver Enum

## Goal

Create a `Driver` backed enum that is the single source of all dialect-specific behavior. Every downstream component resolves SQL differences by calling methods on this enum.

## Design

```php
// src/Schema/Driver.php

#[ClosedSet(
    Domain::database_drivers,
    addCase: <<<TEXT
    1. Add enum case.
    2. Handle every match() arm on the enum (DSN, quoting, type mapping, etc.).
    3. Add driver to test matrix.
    TEXT
)]
enum Driver: string
{
    case mysql  = 'mysql';
    case pgsql  = 'pgsql';
    case sqlite = 'sqlite';
}
```

## Methods

### `quote(string $identifier): string`

Wraps an identifier in the driver-appropriate quoting characters.

```php
public function quote(string $identifier): string
{
    return match ($this) {
        self::mysql  => '`' . $identifier . '`',
        self::pgsql, self::sqlite => '"' . $identifier . '"',
    };
}
```

### `dsn(string $host, int $port, string $database): string`

Builds the PDO DSN string.

```php
public function dsn(string $host, int $port, string $database): string
{
    return match ($this) {
        self::mysql  => sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $host, $port, $database),
        self::pgsql  => sprintf('pgsql:host=%s;port=%d;dbname=%s', $host, $port, $database),
        self::sqlite => 'sqlite:' . $database,
    };
}
```

### `timezoneCommand(string $timezone): ?string`

Returns the SQL command to set the session timezone, or null if the driver does not support it.

```php
public function timezoneCommand(string $timezone): ?string
{
    return match ($this) {
        self::mysql  => "SET time_zone = '$timezone'",
        self::pgsql  => "SET timezone TO '$timezone'",
        self::sqlite => null,
    };
}
```

### `reconnectPatterns(): list<string>`

Returns error message substrings that indicate a dropped connection for this driver.

```php
/** @return list<string> */
public function reconnectPatterns(): array
{
    return match ($this) {
        self::mysql  => ['server has gone away', 'Lost connection'],
        self::pgsql  => ['terminating connection', 'server closed the connection unexpectedly', 'SSL connection has been closed unexpectedly'],
        self::sqlite => [],
    };
}
```

### `autoIncrementSql(): string`

Returns the column-level keyword for auto-incrementing primary keys.

```php
public function autoIncrementSql(): string
{
    return match ($this) {
        self::mysql  => 'AUTO_INCREMENT',
        self::pgsql  => 'GENERATED ALWAYS AS IDENTITY',
        self::sqlite => 'AUTOINCREMENT',
    };
}
```

### `supportsUnsigned(): bool`

```php
public function supportsUnsigned(): bool
{
    return match ($this) {
        self::mysql  => true,
        self::pgsql, self::sqlite => false,
    };
}
```

### `typeSql(Column $Column): string`

Maps a `DataType` + `Column` metadata to the driver-specific SQL type string. This is the largest method — it replaces `DdlBuilder::columnTypeSql()`.

```php
public function typeSql(Column $Column): string
{
    return match ($this) {
        self::mysql  => self::mysqlTypeSql($Column),
        self::pgsql  => self::pgsqlTypeSql($Column),
        self::sqlite => self::sqliteTypeSql($Column),
    };
}
```

#### MySQL type mapping (current behavior, extracted)

```php
private static function mysqlTypeSql(Column $Column): string
{
    return match ($Column->DataType) {
        DataType::VARCHAR    => 'VARCHAR(' . $Column->length . ')',
        DataType::CHAR       => 'CHAR(' . $Column->length . ')',
        DataType::BIGINT     => 'BIGINT' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::INT        => 'INT' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::SMALLINT   => 'SMALLINT' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::TINYINT    => 'TINYINT' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::TEXT       => 'TEXT',
        DataType::MEDIUMTEXT => 'MEDIUMTEXT',
        DataType::LONGTEXT   => 'LONGTEXT',
        DataType::DATETIME   => 'DATETIME',
        DataType::DATE       => 'DATE',
        DataType::TIME       => 'TIME',
        DataType::TIMESTAMP  => 'TIMESTAMP',
        DataType::YEAR       => 'YEAR',
        DataType::DECIMAL    => 'DECIMAL(' . $Column->precision . ',' . $Column->scale . ')',
        DataType::FLOAT      => 'FLOAT' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::DOUBLE     => 'DOUBLE' . ($Column->unsigned ? ' UNSIGNED' : ''),
        DataType::BOOLEAN    => 'BOOLEAN',
        DataType::JSON       => 'JSON',
        DataType::ENUM       => 'ENUM(' . implode(', ', array_map(static fn(string $v) => "'" . addslashes($v) . "'", $Column->values ?? [])) . ')',
        DataType::SET        => 'SET(' . implode(', ', array_map(static fn(string $v) => "'" . addslashes($v) . "'", $Column->values ?? [])) . ')',
        DataType::BINARY     => 'BINARY(' . $Column->length . ')',
        DataType::VARBINARY  => 'VARBINARY(' . $Column->length . ')',
        DataType::BLOB       => 'BLOB',
        DataType::MEDIUMBLOB => 'MEDIUMBLOB',
        DataType::LONGBLOB   => 'LONGBLOB',
    };
}
```

#### PostgreSQL type mapping

```php
private static function pgsqlTypeSql(Column $Column): string
{
    return match ($Column->DataType) {
        DataType::VARCHAR    => 'VARCHAR(' . $Column->length . ')',
        DataType::CHAR       => 'CHAR(' . $Column->length . ')',
        DataType::BIGINT     => $Column->auto_increment ? 'BIGSERIAL' : 'BIGINT',
        DataType::INT        => $Column->auto_increment ? 'SERIAL' : 'INTEGER',
        DataType::SMALLINT   => $Column->auto_increment ? 'SMALLSERIAL' : 'SMALLINT',
        DataType::TINYINT    => 'SMALLINT',
        DataType::TEXT, DataType::MEDIUMTEXT, DataType::LONGTEXT => 'TEXT',
        DataType::DATETIME   => 'TIMESTAMP',
        DataType::DATE       => 'DATE',
        DataType::TIME       => 'TIME',
        DataType::TIMESTAMP  => 'TIMESTAMP',
        DataType::YEAR       => 'SMALLINT',
        DataType::DECIMAL    => 'NUMERIC(' . $Column->precision . ',' . $Column->scale . ')',
        DataType::FLOAT      => 'REAL',
        DataType::DOUBLE     => 'DOUBLE PRECISION',
        DataType::BOOLEAN    => 'BOOLEAN',
        DataType::JSON       => 'JSONB',
        DataType::ENUM       => 'TEXT',
        DataType::SET        => 'TEXT',
        DataType::BINARY     => 'BYTEA',
        DataType::VARBINARY  => 'BYTEA',
        DataType::BLOB, DataType::MEDIUMBLOB, DataType::LONGBLOB => 'BYTEA',
    };
}
```

#### SQLite type mapping

```php
private static function sqliteTypeSql(Column $Column): string
{
    return match ($Column->DataType) {
        DataType::VARCHAR, DataType::CHAR, DataType::TEXT, DataType::MEDIUMTEXT, DataType::LONGTEXT => 'TEXT',
        DataType::BIGINT, DataType::INT, DataType::SMALLINT, DataType::TINYINT, DataType::YEAR => 'INTEGER',
        DataType::DATETIME, DataType::DATE, DataType::TIME, DataType::TIMESTAMP => 'TEXT',
        DataType::DECIMAL, DataType::FLOAT, DataType::DOUBLE => 'REAL',
        DataType::BOOLEAN => 'INTEGER',
        DataType::JSON => 'TEXT',
        DataType::ENUM, DataType::SET => 'TEXT',
        DataType::BINARY, DataType::VARBINARY, DataType::BLOB, DataType::MEDIUMBLOB, DataType::LONGBLOB => 'BLOB',
    };
}
```

### `tableOptions(?Engine $Engine, ?Charset $Charset, ?Collation $Collation): string`

Returns the driver-specific table suffix for CREATE TABLE, or empty string.

```php
public function tableOptions(?Engine $Engine, ?Charset $Charset, ?Collation $Collation): string
{
    return match ($this) {
        self::mysql => ($Engine !== null ? ' ENGINE=' . $Engine->value : '')
            . ($Charset !== null ? ' DEFAULT CHARSET=' . $Charset->value : '')
            . ($Collation !== null ? ' COLLATE=' . $Collation->value : ''),
        self::pgsql, self::sqlite => '',
    };
}
```

### `transactionalDdl(): bool`

Whether DDL statements can be wrapped in transactions.

```php
public function transactionalDdl(): bool
{
    return match ($this) {
        self::mysql  => false,
        self::pgsql  => true,
        self::sqlite => true,
    };
}
```

### `enumConstraint(string $column, array $values): ?string`

PostgreSQL needs a CHECK constraint to emulate ENUM. MySQL and SQLite return null.

```php
/** @param list<string> $values */
public function enumConstraint(string $column, array $values): ?string
{
    return match ($this) {
        self::pgsql => 'CHECK (' . $this->quote($column) . ' IN ('
            . implode(', ', array_map(static fn(string $v) => "'" . addslashes($v) . "'", $values))
            . '))',
        self::mysql, self::sqlite => null,
    };
}
```

### `defaultPort(): int`

```php
public function defaultPort(): int
{
    return match ($this) {
        self::mysql  => 3306,
        self::pgsql  => 5432,
        self::sqlite => 0,
    };
}
```

### `requiresHostPort(): bool`

SQLite uses file paths, not network connections.

```php
public function requiresHostPort(): bool
{
    return match ($this) {
        self::mysql, self::pgsql => true,
        self::sqlite => false,
    };
}
```

## Implementation Steps

### Step 1: Create the Driver enum

- File: `src/Schema/Driver.php`
- Backed string enum with cases: `mysql`, `pgsql`, `sqlite`
- `#[ClosedSet]` attribute with `addCase` instructions
- All methods listed above

### Step 2: Add to Domain enum

- Add `database_drivers` case to `src/UI/Domain.php` for the `#[ClosedSet]` reference

### Step 3: Run check:all

- `./run fix:all` to apply style and rector fixes
- `./run check:all` — all checks must pass

## Files Created

| File | Type |
|------|------|
| `src/Schema/Driver.php` | Enum |

## Files Modified

| File | Change |
|------|--------|
| `src/UI/Domain.php` | Add `database_drivers` case |
