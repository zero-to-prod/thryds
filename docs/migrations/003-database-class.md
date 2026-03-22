# Phase 3: Database Class — Driver-Aware Connection

## Goal

Refactor `Database` to read the `Driver` from `DatabaseConfig`, delegate timezone setup and reconnect detection to `Driver` methods, and remove the `#[ReconnectOn]` attributes. The existing cached reflection pattern is preserved for connection options and timezone.

## Current State

```php
// src/Database.php
#[Timezone('+00:00')]
#[ReconnectOn('server has gone away')]
#[ReconnectOn('Lost connection')]
class Database
{
    private ?PDO $PDO = null;
    private readonly DatabaseConfig $DatabaseConfig;

    /** @var array<int, mixed>|null */
    private static ?array $connection_options = null;
    private static ?string $timezone = null;
    private static bool $timezone_resolved = false;
    /** @var list<string>|null */
    private static ?array $reconnect_messages = null;
```

The class already caches reflection results in static properties (`$connection_options`, `$timezone`, `$reconnect_messages`). The `connect()` method uses `resolveConnectionOptions()`, `resolveTimezone()`, and `isGoneAway()` uses `resolveReconnectMessages()`.

MySQL-specific: `SET time_zone = '...'` syntax on line 186, MySQL error strings in `#[ReconnectOn]`.

## Target State

```php
#[ConnectionOption(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION)]
#[ConnectionOption(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC)]
#[ConnectionOption(PDO::ATTR_EMULATE_PREPARES, false)]
#[ConnectionOption(PDO::ATTR_PERSISTENT, false)]
#[Timezone('+00:00')]
class Database
{
    private ?PDO $PDO = null;
    private readonly DatabaseConfig $DatabaseConfig;

    /** @var array<int, mixed>|null */
    private static ?array $connection_options = null;
    private static ?string $timezone = null;
    private static bool $timezone_resolved = false;

    public function __construct(DatabaseConfig $DatabaseConfig)
    {
        $this->DatabaseConfig = $DatabaseConfig;
    }

    public function driver(): Driver
    {
        return $this->DatabaseConfig->driver;
    }

    // ... all(), one(), scalar(), execute(), insert(), transaction() unchanged ...

    private static function connect(DatabaseConfig $DatabaseConfig): PDO
    {
        $PDO = new PDO(
            dsn: $DatabaseConfig->dsn,
            username: $DatabaseConfig->username,
            password: $DatabaseConfig->password,
            options: self::resolveConnectionOptions(),
        );

        $timezone = self::resolveTimezone();
        if ($timezone !== null) {
            $command = $DatabaseConfig->driver->timezoneCommand($timezone);
            if ($command !== null) {
                $PDO->exec($command);
            }
        }

        return $PDO;
    }

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

    // resolveConnectionOptions() — unchanged (cached reflection)
    // resolveTimezone() — unchanged (cached reflection)
    // resolveReconnectMessages() — DELETED
}
```

## Changes

| What | Before | After |
|------|--------|-------|
| `#[ReconnectOn]` attributes | Two on class | Removed |
| `#[Timezone]` attribute | Stays on class | Stays — timezone SQL delegated to `Driver::timezoneCommand()` |
| `$reconnect_messages` static cache | Caches reflected `#[ReconnectOn]` attrs | Deleted — `Driver::reconnectPatterns()` is already a pure method |
| `resolveReconnectMessages()` | Reflects `#[ReconnectOn]` attrs | Deleted |
| `isGoneAway()` | `static`, calls `self::resolveReconnectMessages()` | Instance method, calls `$this->DatabaseConfig->driver->reconnectPatterns()` |
| `connect()` timezone | `$PDO->exec("SET time_zone = '...'")` | `$DatabaseConfig->driver->timezoneCommand()` — null for SQLite |
| `run()` reconnect call | `self::isGoneAway()` | `$this->isGoneAway()` |
| `driver()` accessor | Does not exist | Public method exposing `$DatabaseConfig->driver` |
| `$connection_options` cache | Unchanged | Unchanged |
| `$timezone` cache | Unchanged | Unchanged |

## Attribute Removal: `#[ReconnectOn]`

The `#[ReconnectOn]` attribute becomes unnecessary:
- Reconnect patterns are inherent to the driver, not configurable per-deployment
- The `Driver` enum owns this knowledge
- No reflection overhead — `Driver::reconnectPatterns()` is a pure match expression
- The static cache `$reconnect_messages` and `resolveReconnectMessages()` are deleted

The `ReconnectOn.php` attribute file can be deleted if no other code references it.

## `#[Timezone]` Retained

The `#[Timezone]` attribute stays because:
- The timezone value (`+00:00`) is a deployment choice, not a driver property
- The attribute declares the desired timezone; `Driver` translates it to the correct SQL
- SQLite ignores it (`timezoneCommand()` returns null)
- The existing cache (`$timezone`, `$timezone_resolved`) remains unchanged

## `driver()` Accessor

Query traits and `DdlBuilder` need the active `Driver`. The `driver()` method on `Database` provides this. The `#[Connection]` attribute already resolves `Database` instances — adding `driver()` completes the chain:

```
Query → #[Connection] → Database → driver() → Driver
```

## Implementation Steps

### Step 1: Add `driver()` method to `Database`

### Step 2: Refactor `connect()` timezone handling

- Call `$DatabaseConfig->driver->timezoneCommand($timezone)` instead of hardcoded SQL
- Skip `exec()` if command is null (SQLite)

### Step 3: Refactor `isGoneAway()`

- Change from `static` to instance method
- Replace `self::resolveReconnectMessages()` with `$this->DatabaseConfig->driver->reconnectPatterns()`

### Step 4: Update `run()` method

- Change `self::isGoneAway()` to `$this->isGoneAway()`

### Step 5: Remove `#[ReconnectOn]` attributes from class declaration

- Delete the two `#[ReconnectOn]` attributes
- Delete `$reconnect_messages` static property
- Delete `resolveReconnectMessages()` method
- Remove `use ReconnectOn` import

### Step 6: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Database.php` | Add `driver()`, refactor `connect()` and `isGoneAway()`, remove `#[ReconnectOn]` and its cache |

## Files Potentially Deleted

| File | Condition |
|------|-----------|
| `src/Attributes/ReconnectOn.php` | Delete if no other code references it after this phase |
