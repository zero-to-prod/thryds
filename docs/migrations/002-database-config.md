# Phase 2: DatabaseConfig — Driver-Aware DSN

## Goal

Replace the hardcoded `mysql:` DSN in `DatabaseConfig::computeDsn()` with `Driver::dsn()`. The `Driver` becomes a first-class property of the config.

## Current State

```php
// src/DatabaseConfig.php — line 64-70
public static function computeDsn(mixed $value, array $context): string
{
    return sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        (string) ($context[self::host] ?? ''),
        (int) ($context[self::port] ?? 3306),
        (string) ($context[self::database] ?? ''),
    );
}
```

Hardcoded: `mysql:` prefix, `charset=utf8mb4`, default port `3306`.

## Target State

```php
readonly class DatabaseConfig
{
    use DataModel;

    /** @see $driver */
    public const string driver = 'driver';
    /** @see $host */
    public const string host = 'host';
    /** @see $port */
    public const string port = 'port';
    /** @see $database */
    public const string database = 'database';
    /** @see $username */
    public const string username = 'username';
    /** @see $password */
    public const string password = 'password';
    /** @see $dsn */
    public const string dsn = 'dsn';

    #[Describe([Describe::default => Driver::mysql])]
    public Driver $driver;

    #[Describe([Describe::default => ''])]
    public string $host;

    #[Describe([Describe::cast => [self::class, 'computePort'], Describe::default => 0])]
    public int $port;

    #[Describe([Describe::default => ''])]
    public string $database;

    #[Describe([Describe::default => ''])]
    public string $username;

    #[Describe([Describe::default => ''])]
    public string $password;

    #[Describe([Describe::cast => [self::class, 'computeDsn'], Describe::default => ''])]
    public string $dsn;

    public static function fromEnv(): self
    {
        return self::from([
            self::driver   => Driver::tryFrom((string) getenv(Env::DB_DRIVER)) ?? Driver::mysql,
            self::host     => (string) getenv(Env::DB_HOST),
            self::port     => 0,
            self::database => (string) getenv(Env::DB_DATABASE),
            self::username => (string) getenv(Env::DB_USERNAME),
            self::password => (string) getenv(Env::DB_PASSWORD),
            self::dsn      => '',
        ]);
    }

    /** @param array<string, mixed> $context */
    public static function computePort(mixed $value, array $context): int
    {
        $env_port = (int) (getenv(Env::DB_PORT) ?: 0);
        if ($env_port > 0) {
            return $env_port;
        }

        /** @var Driver $driver */
        $driver = $context[self::driver] ?? Driver::mysql;

        return $driver->defaultPort();
    }

    /** @param array<string, mixed> $context */
    public static function computeDsn(mixed $value, array $context): string
    {
        /** @var Driver $driver */
        $driver = $context[self::driver] ?? Driver::mysql;

        return $driver->dsn(
            host: (string) ($context[self::host] ?? ''),
            port: (int) ($context[self::port] ?? $driver->defaultPort()),
            database: (string) ($context[self::database] ?? ''),
        );
    }
}
```

## Changes

| What | Before | After |
|------|--------|-------|
| `$driver` property | Does not exist | `public Driver $driver` with default `Driver::mysql` |
| `$port` default | Hardcoded `3306` | `Driver::defaultPort()` |
| `computeDsn()` | `sprintf('mysql:...')` | `$driver->dsn(...)` |
| `fromEnv()` | No driver param | Reads `DB_DRIVER` env var, falls back to `mysql` |

## Environment Variable

Add `DB_DRIVER` to the `Env` class/constants:

```php
// In Env class or wherever env keys are declared
public const string DB_DRIVER = 'DB_DRIVER';
```

Valid values: `mysql`, `pgsql`, `sqlite`. Defaults to `mysql` when absent — backward compatible.

## Backward Compatibility

- Default driver is `mysql` — existing deployments with no `DB_DRIVER` env var behave identically
- Default port falls through to `Driver::defaultPort()` which returns `3306` for MySQL
- DSN format for MySQL is unchanged

## Implementation Steps

### Step 1: Add `DB_DRIVER` to Env

- Add constant to wherever env key names are defined

### Step 2: Add `$driver` property to DatabaseConfig

- Type: `Driver`
- Default: `Driver::mysql`
- Add `self::driver` constant

### Step 3: Refactor `computePort()`

- Read driver from context
- Use `Driver::defaultPort()` as fallback instead of hardcoded `3306`

### Step 4: Refactor `computeDsn()`

- Read driver from context
- Call `$driver->dsn()` instead of `sprintf('mysql:...')`

### Step 5: Update `fromEnv()`

- Read `DB_DRIVER` env var
- Use `Driver::tryFrom()` with `Driver::mysql` fallback
- Pass `0` for port (let `computePort()` resolve)

### Step 6: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/DatabaseConfig.php` | Add `$driver` property, refactor `computeDsn()` and port default |
| `src/Env.php` (or equivalent) | Add `DB_DRIVER` constant |
