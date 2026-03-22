# Phase 7: Migrator — Transactional DDL

## Goal

Make the `Migrator` aware of `Driver::transactionalDdl()` so that PostgreSQL and SQLite migrations are wrapped in transactions, while MySQL migrations continue to run without transaction wrapping.

## Current State

```php
// src/Migrator.php
#[MigrationsSource(
    directory: 'migrations',
    namespace: 'ZeroToProd\\Thryds\\Migrations',
)]
readonly class Migrator
{
    use RowAccess;

    private MigrationDiscovery $MigrationDiscovery;
    private MigrationStatusResolver $MigrationStatusResolver;

    // ...

    private function runUp(string $class): void
    {
        $this->Database->execute(self::resolveMigrationAction($class)->upSql());
    }

    private function runDown(string $class): void
    {
        $this->Database->execute(self::resolveMigrationAction($class)->downSql());
    }

    private static function resolveMigrationAction(string $class): object
    {
        foreach (new ReflectionClass($class)->getAttributes() as $attribute) {
            $attrClass = new ReflectionClass($attribute->getName());
            if ($attrClass->getAttributes(MigrationAction::class) !== []) {
                return $attribute->newInstance();
            }
        }

        throw new RuntimeException("$class must declare a MigrationAction attribute...");
    }
}
```

Key differences from the initial plan:
- `MigrationInterface` no longer exists — all migrations are attribute-driven
- `resolveMigrationAction()` uses the `#[MigrationAction]` marker attribute pattern
- `MigrationDiscovery` and `MigrationStatusResolver` are separate classes
- `Migrator::create()` factory reads `#[MigrationsSource]` from self
- `status()` returns `list<MigrationStatusRow>` (typed DTOs, not raw arrays)
- `rollback()` uses `SelectLastMigrationQuery::oneRow()` instead of building SQL inline

## Target State

### `runUp()` and `runDown()`

```php
private function runUp(string $class): void
{
    $Driver = $this->Database->driver();
    $action = self::resolveMigrationAction($class);
    $sql = $action->upSql($Driver);

    if ($Driver->transactionalDdl()) {
        $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
    } else {
        $this->Database->execute($sql);
    }
}

private function runDown(string $class): void
{
    $Driver = $this->Database->driver();
    $action = self::resolveMigrationAction($class);
    $sql = $action->downSql($Driver);

    if ($Driver->transactionalDdl()) {
        $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
    } else {
        $this->Database->execute($sql);
    }
}
```

### `ensureTable()` — Driver Passthrough

```php
public function ensureTable(): void
{
    $this->Database->execute(DdlBuilder::createTableSql(Migration::class, $this->Database->driver()));
}
```

### `resolveMigrationAction()` — unchanged

The duck-type dispatch already works. The `Driver` parameter is passed when calling `upSql()`/`downSql()`, not when resolving the action.

## Behavioral Differences by Driver

| Behavior | MySQL | PostgreSQL | SQLite |
|----------|-------|------------|--------|
| DDL in transaction | Implicit commit | Fully transactional | Fully transactional |
| Failed migration | Partial state possible | Rolled back cleanly | Rolled back cleanly |
| `ensureTable()` timing | Before any transaction | Can be inside transaction | Can be inside transaction |
| `#[RawSql]` in transaction | Raw SQL may trigger implicit commit | Wrapped in transaction | Wrapped in transaction |

## `#[RawSql]` and Transactional DDL

`RawSql` migrations contain consumer-authored SQL. If the SQL includes DDL on MySQL, it will implicitly commit. On PostgreSQL/SQLite, the transaction wrapping applies. The `#[RawSql]` docblock should note this distinction.

## Docblock Updates

The Migrator class docblock currently says:

```
DDL note: ensureTable() and migration actions that run DDL
(CREATE TABLE, ALTER TABLE, etc.) cause MySQL to implicitly commit any open
transaction. Call ensureTable() before opening a transaction.
```

Updated to:

```
Transaction behavior depends on the active driver:
- MySQL: DDL causes implicit commit; migrations run without wrapping.
- PostgreSQL/SQLite: DDL is transactional; Migrator wraps up/down in a transaction.
```

## Classes Unchanged

| Class | Why |
|-------|-----|
| `MigrationDiscovery` | Filesystem scanning — driver-agnostic |
| `MigrationStatusResolver` | Compares checksums — driver-agnostic |
| `MigrationStatusRow` | Typed DTO — driver-agnostic |
| `RowAccess` | Type-narrowing — driver-agnostic |
| `SelectMigrationsQuery` | Uses `DbRead` trait — quoting handled in Phase 6 |
| `SelectLastMigrationQuery` | Uses `DbRead` trait — quoting handled in Phase 6 |
| `InsertMigrationQuery` | Uses `DbCreate` trait — quoting handled in Phase 6 |
| `DeleteMigrationQuery` | Uses `DbDelete` trait — quoting handled in Phase 6 |

## Implementation Steps

### Step 1: Update `ensureTable()`

- Pass `$this->Database->driver()` to `DdlBuilder::createTableSql()`

### Step 2: Update `runUp()`

- Resolve `$Driver` from `$this->Database->driver()`
- Pass `$Driver` to `$action->upSql($Driver)`
- Wrap in `$this->Database->transaction()` when `$Driver->transactionalDdl()` is true

### Step 3: Update `runDown()`

- Same pattern as `runUp()`

### Step 4: Update class docblock

- Replace MySQL-specific DDL note with driver-aware explanation

### Step 5: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Migrator.php` | `ensureTable()` passes Driver, `runUp()`/`runDown()` wrap in transactions per driver, docblock updated |
