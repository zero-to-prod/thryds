# Phase 2: DatabaseConfig — Driver-Aware DSN

## Goal

Replace the hardcoded `mysql:` DSN in `DatabaseConfig::computeDsn()` with `Driver::dsn()`. The `Driver` becomes a first-class property of the config, populated via the existing `#[EnvVar]` reflection pattern.

## Current State

```php
// src/DatabaseConfig.php
readonly class DatabaseConfig
{
    use DataModel;

    #[EnvVar(Env::DB_HOST)]
    #[Describe([Describe::default => ''])]
    public string $host;

    #[EnvVar(Env::DB_PORT)]
    #[Describe([Describe::default => 3306])]
    public int $port;

    // ... other properties ...

    public static function fromEnv(): self
    {
        return self::fromEnvData(getenv());
    }

    public static function fromEnvData(array $env): self
    {
        $data = [];
        foreach (new ReflectionClass(static::class)->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attrs = $property->getAttributes(EnvVar::class);
            if ($attrs === []) {
                continue;
            }
            $key = $attrs[0]->newInstance()->key;
            if (isset($env[$key])) {
                $data[$property->getName()] = $env[$key];
            }
        }

        return self::from($data);
    }

    public static function computeDsn(mixed $value, array $context): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            (string) ($context[self::host] ?? ''),
            (int) ($context[self::port] ?? 3306),
            (string) ($context[self::database] ?? ''),
        );
    }
}
```

`fromEnvData()` already uses `#[EnvVar]` reflection to map env vars to properties. The `$driver` property fits this pattern naturally.

Hardcoded: `mysql:` prefix, `charset=utf8mb4`, default port `3306`.

## Target State

```php
readonly class DatabaseConfig
{
    use DataModel;

    /** @see $driver */
    public const string driver = 'driver';
    // ... existing constants ...

    #[EnvVar(Env::DB_DRIVER)]
    #[Describe([Describe::cast => [self::class, 'castDriver'], Describe::default => Driver::mysql])]
    public Driver $driver;

    #[EnvVar(Env::DB_HOST)]
    #[Describe([Describe::default => ''])]
    public string $host;

    #[EnvVar(Env::DB_PORT)]
    #[Describe([Describe::cast => [self::class, 'computePort'], Describe::default => 0])]
    public int $port;

    // ... username, password unchanged ...

    #[Describe([Describe::default => [self::class, 'computeDsn']])]
    public string $dsn;

    public static function castDriver(mixed $value, array $context): Driver
    {
        if ($value instanceof Driver) {
            return $value;
        }

        return Driver::tryFrom((string) $value) ?? Driver::mysql;
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
}
```

Note: `fromEnv()` and `fromEnvData()` are unchanged — the `#[EnvVar]` reflection loop automatically picks up `DB_DRIVER` because the new `$driver` property carries `#[EnvVar(Env::DB_DRIVER)]`. The `#[Describe]` cast handles string→`Driver` coercion.

## Changes

| What | Before | After |
|------|--------|-------|
| `$driver` property | Does not exist | `public Driver $driver` with `#[EnvVar(Env::DB_DRIVER)]` |
| `$port` default | Hardcoded `3306` | `Driver::defaultPort()` via `computePort()` cast |
| `computeDsn()` | `sprintf('mysql:...')` | `$driver->dsn(...)` |
| `fromEnv()` / `fromEnvData()` | Unchanged | Unchanged — reflection auto-discovers `#[EnvVar]` on `$driver` |

## Environment Variable

Add `DB_DRIVER` to the `Env` class:

```php
public const string DB_DRIVER = 'DB_DRIVER';
```

Valid values: `mysql`, `pgsql`, `sqlite`. Defaults to `mysql` when absent — backward compatible.

## Backward Compatibility

- Default driver is `Driver::mysql` — existing deployments with no `DB_DRIVER` env var behave identically
- Default port falls through to `Driver::defaultPort()` which returns `3306` for MySQL
- DSN format for MySQL is unchanged
- `fromEnvData()` does not need modification — `#[EnvVar]` reflection handles the new property

## Implementation Steps

### Step 1: Add `DB_DRIVER` to Env

### Step 2: Add `$driver` property to DatabaseConfig

- Type: `Driver`
- `#[EnvVar(Env::DB_DRIVER)]`
- `#[Describe]` with cast to handle string→Driver coercion and `Driver::mysql` default
- Add `self::driver` constant

### Step 3: Add `castDriver()` method

- Handle both `Driver` enum and string input from env

### Step 4: Refactor `computePort()`

- Read driver from context
- Use `Driver::defaultPort()` as fallback instead of hardcoded `3306`

### Step 5: Refactor `computeDsn()`

- Read driver from context
- Call `$driver->dsn()` instead of `sprintf('mysql:...')`

### Step 6: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/DatabaseConfig.php` | Add `$driver` property with `#[EnvVar]`, refactor `computeDsn()` and port default |
| `src/Env.php` (or equivalent) | Add `DB_DRIVER` constant |
