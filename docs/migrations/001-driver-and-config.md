# Phase 1: Driver Enum + DatabaseConfig + Database

## Goal

Create the `Driver` enum with all dialect methods. Wire it into `DatabaseConfig` via `#[EnvVar]`. Add `driver()` to `Database` and delegate timezone/reconnect to `Driver`. Remove `#[ReconnectOn]`.

This is the foundation phase — everything downstream resolves `Driver` from here.

## Part A: Driver Enum

### File: `src/Schema/Driver.php`

```php
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

### Methods

#### `quote(string $identifier): string`

```php
public function quote(string $identifier): string
{
    return match ($this) {
        self::mysql  => '`' . $identifier . '`',
        self::pgsql, self::sqlite => '"' . $identifier . '"',
    };
}
```

#### `dsn(string $host, int $port, string $database): string`

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

#### `timezoneCommand(string $timezone): ?string`

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

#### `reconnectPatterns(): list<string>`

```php
public function reconnectPatterns(): array
{
    return match ($this) {
        self::mysql  => ['server has gone away', 'Lost connection'],
        self::pgsql  => ['terminating connection', 'server closed the connection unexpectedly', 'SSL connection has been closed unexpectedly'],
        self::sqlite => [],
    };
}
```

#### `autoIncrementSql(): string`

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

#### `supportsUnsigned(): bool`

```php
public function supportsUnsigned(): bool
{
    return $this === self::mysql;
}
```

#### `typeSql(Column $Column): string`

Maps `DataType` + column metadata to driver-specific SQL type string. Replaces `DdlBuilder::columnTypeSql()`.

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

**MySQL** — current `DdlBuilder::columnTypeSql()` logic extracted verbatim.

**PostgreSQL:**

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
        DataType::ENUM, DataType::SET => 'TEXT',
        DataType::BINARY, DataType::VARBINARY => 'BYTEA',
        DataType::BLOB, DataType::MEDIUMBLOB, DataType::LONGBLOB => 'BYTEA',
    };
}
```

**SQLite:**

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

#### `tableOptions(?Engine $Engine, ?Charset $Charset, ?Collation $Collation): string`

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

#### `transactionalDdl(): bool`

```php
public function transactionalDdl(): bool
{
    return $this !== self::mysql;
}
```

#### `enumConstraint(string $column, array $values): ?string`

```php
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

#### `defaultPort(): int`

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

## Part B: DatabaseConfig

### Current

```php
#[EnvVar(Env::DB_PORT)]
#[Describe([Describe::default => 3306])]
public int $port;

public static function computeDsn(mixed $value, array $context): string
{
    return sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', ...);
}
```

### Target

Add `$driver` property, refactor `computePort()` and `computeDsn()`:

```php
/** @see $driver */
public const string driver = 'driver';

#[EnvVar(Env::DB_DRIVER)]
#[Describe([Describe::cast => [self::class, 'castDriver'], Describe::default => Driver::mysql])]
public Driver $driver;

#[EnvVar(Env::DB_PORT)]
#[Describe([Describe::cast => [self::class, 'computePort'], Describe::default => 0])]
public int $port;

public static function castDriver(mixed $value, array $context): Driver
{
    return $value instanceof Driver ? $value : (Driver::tryFrom((string) $value) ?? Driver::mysql);
}

public static function computePort(mixed $value, array $context): int
{
    $port = (int) $value;
    if ($port > 0) {
        return $port;
    }

    $driver = $context[self::driver] ?? Driver::mysql;
    $driver = $driver instanceof Driver ? $driver : (Driver::tryFrom((string) $driver) ?? Driver::mysql);

    return $driver->defaultPort();
}

public static function computeDsn(mixed $value, array $context): string
{
    $driver = $context[self::driver] ?? Driver::mysql;
    $driver = $driver instanceof Driver ? $driver : (Driver::tryFrom((string) $driver) ?? Driver::mysql);

    return $driver->dsn(
        host: (string) ($context[self::host] ?? ''),
        port: (int) ($context[self::port] ?? $driver->defaultPort()),
        database: (string) ($context[self::database] ?? ''),
    );
}
```

`fromEnv()` and `fromEnvData()` are **unchanged** — the `#[EnvVar]` reflection loop auto-discovers `DB_DRIVER`.

Add `DB_DRIVER` to `Env`:

```php
public const string DB_DRIVER = 'DB_DRIVER';
```

## Part C: Database

### Add `driver()` accessor

```php
public function driver(): Driver
{
    return $this->DatabaseConfig->driver;
}
```

### Refactor `connect()` — timezone

```php
// Before
$PDO->exec("SET time_zone = '" . $timezone . "'");

// After
$command = $DatabaseConfig->driver->timezoneCommand($timezone);
if ($command !== null) {
    $PDO->exec($command);
}
```

### Refactor `isGoneAway()` — reconnect

```php
// Before (static, reflects #[ReconnectOn] attributes)
private static function isGoneAway(PDOException $PDOException): bool
{
    $message = $PDOException->getMessage();
    foreach (self::resolveReconnectMessages() as $needle) { ... }
}

// After (instance method, reads from Driver)
private function isGoneAway(PDOException $PDOException): bool
{
    $message = $PDOException->getMessage();
    foreach ($this->DatabaseConfig->driver->reconnectPatterns() as $pattern) {
        if (str_contains(haystack: $message, needle: $pattern)) {
            return true;
        }
    }

    return false;
}
```

### Remove

- `#[ReconnectOn('server has gone away')]` and `#[ReconnectOn('Lost connection')]` from class
- `private static ?array $reconnect_messages` property
- `resolveReconnectMessages()` method
- `use ReconnectOn` import
- Change `self::isGoneAway()` to `$this->isGoneAway()` in `run()`

### Keep unchanged

- `#[ConnectionOption]` attributes — PDO options are driver-agnostic
- `#[Timezone('+00:00')]` — declares the value; `Driver` translates
- `$connection_options` cache — still needed
- `$timezone` / `$timezone_resolved` cache — still needed

## Implementation Steps

1. Create `src/Schema/Driver.php` with all methods
2. Add `database_drivers` case to `src/UI/Domain.php`
3. Add `DB_DRIVER` to `Env`
4. Add `$driver` property + casts to `DatabaseConfig`
5. Add `driver()` to `Database`
6. Refactor `Database::connect()` timezone → `Driver::timezoneCommand()`
7. Refactor `Database::isGoneAway()` → `Driver::reconnectPatterns()`
8. Remove `#[ReconnectOn]` from `Database`, delete cache
9. Run `./run fix:all`

## Files

| File | Action |
|------|--------|
| `src/Schema/Driver.php` | Create |
| `src/UI/Domain.php` | Add case |
| `src/Env.php` | Add `DB_DRIVER` |
| `src/DatabaseConfig.php` | Add `$driver`, refactor DSN/port |
| `src/Database.php` | Add `driver()`, refactor timezone/reconnect, remove `#[ReconnectOn]` |
| `src/Attributes/ReconnectOn.php` | Delete if no other references |
