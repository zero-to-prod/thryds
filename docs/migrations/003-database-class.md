# Phase 3: Database Class — Driver-Aware Connection

## Goal

Refactor `Database` to read the `Driver` from `DatabaseConfig`, delegate timezone setup and reconnect detection to `Driver` methods, and remove the `#[ReconnectOn]` and `#[Timezone]` attributes from the class declaration.

## Current State

```php
// src/Database.php — lines 35-37
#[Timezone('+00:00')]
#[ReconnectOn('server has gone away')]
#[ReconnectOn('Lost connection')]
class Database
```

```php
// connect() — line 182-185
$timezone_attrs = $ReflectionClass->getAttributes(Timezone::class);
if ($timezone_attrs !== []) {
    $PDO->exec("SET time_zone = '" . $timezone_attrs[0]->newInstance()->timezone . "'");
}
```

```php
// isGoneAway() — line 190-199
foreach (new ReflectionClass(self::class)->getAttributes(ReconnectOn::class) as $attr) {
    if (str_contains(haystack: $message, needle: $attr->newInstance()->message)) {
        return true;
    }
}
```

MySQL-specific: `SET time_zone` syntax, MySQL error strings.

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
        $ReflectionClass = new ReflectionClass(self::class);

        $options = [];
        foreach ($ReflectionClass->getAttributes(ConnectionOption::class) as $attr) {
            $ConnectionOption = $attr->newInstance();
            $options[$ConnectionOption->attribute] = $ConnectionOption->value;
        }

        $PDO = new PDO(
            dsn: $DatabaseConfig->dsn,
            username: $DatabaseConfig->username,
            password: $DatabaseConfig->password,
            options: $options,
        );

        $timezone_attrs = $ReflectionClass->getAttributes(Timezone::class);
        if ($timezone_attrs !== []) {
            $command = $DatabaseConfig->driver->timezoneCommand($timezone_attrs[0]->newInstance()->timezone);
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
}
```

## Changes

| What | Before | After |
|------|--------|-------|
| `#[ReconnectOn]` attributes | Two on class declaration | Removed — `Driver::reconnectPatterns()` |
| `#[Timezone]` attribute | Stays on class | Stays — but timezone SQL delegated to `Driver::timezoneCommand()` |
| `isGoneAway()` | Reflects `#[ReconnectOn]` via `ReflectionClass` | Iterates `$this->DatabaseConfig->driver->reconnectPatterns()` |
| `connect()` timezone | Hardcoded `SET time_zone = '...'` | `$driver->timezoneCommand()` — returns null for SQLite |
| `isGoneAway()` scope | `static` (re-reflects every call) | Instance method (reads from config) |
| `driver()` accessor | Does not exist | Public method exposing `$DatabaseConfig->driver` |

## Attribute Removal: `#[ReconnectOn]`

The `#[ReconnectOn]` attribute becomes unnecessary because:
- Reconnect patterns are inherent to the driver, not configurable per-deployment
- The `Driver` enum owns this knowledge
- No reflection overhead on every failed query

The `ReconnectOn.php` attribute class can be deleted or retained for backward compatibility. If the package supports user-defined reconnect patterns in the future, it can be re-introduced.

## `#[Timezone]` Retained

The `#[Timezone]` attribute stays because:
- The timezone value (`+00:00`) is a deployment choice, not a driver property
- The attribute declares the desired timezone; `Driver` translates it to SQL
- SQLite ignores it (returns null from `timezoneCommand`)

## `driver()` Accessor

Query traits and `DdlBuilder` need the active `Driver` to generate correct SQL. The `driver()` method on `Database` provides this without exposing `DatabaseConfig`.

```php
public function driver(): Driver
{
    return $this->DatabaseConfig->driver;
}
```

## Implementation Steps

### Step 1: Add `driver()` method to `Database`

### Step 2: Refactor `connect()` timezone handling

- Read `#[Timezone]` attribute (unchanged)
- Call `$DatabaseConfig->driver->timezoneCommand($timezone)` instead of hardcoded SQL
- Skip `exec()` if command is null (SQLite)

### Step 3: Refactor `isGoneAway()`

- Change from `static` to instance method
- Replace `ReflectionClass` attribute loop with `$this->DatabaseConfig->driver->reconnectPatterns()`

### Step 4: Update `run()` method

- `isGoneAway()` call changes from `self::isGoneAway()` to `$this->isGoneAway()`

### Step 5: Remove `#[ReconnectOn]` attributes from class declaration

- Delete `#[ReconnectOn('server has gone away')]`
- Delete `#[ReconnectOn('Lost connection')]`
- Remove `use ZeroToProd\Thryds\Attributes\ReconnectOn;` import

### Step 6: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Database.php` | Add `driver()`, refactor `connect()` and `isGoneAway()`, remove `#[ReconnectOn]` |

## Files Potentially Deleted

| File | Condition |
|------|-----------|
| `src/Attributes/ReconnectOn.php` | Delete if no other code references it after this phase |
