# Phase 4: Migrator — Transactional DDL

## Goal

Wrap migration DDL in transactions when the driver supports it. MySQL implicitly commits on DDL; PostgreSQL and SQLite support transactional DDL.

## Design

The Migrator already has `$this->Database`. `$this->Database->driver()` gives the `Driver`. No parameter threading — the Migrator checks `transactionalDdl()` and wraps accordingly.

Migration actions are **not modified** — they don't know or care about transactions. The Migrator owns the transaction boundary.

## Current

```php
private function runUp(string $class): void
{
    $this->Database->execute(self::resolveMigrationAction($class)->upSql());
}

private function runDown(string $class): void
{
    $this->Database->execute(self::resolveMigrationAction($class)->downSql());
}
```

## Target

```php
private function runUp(string $class): void
{
    $sql = self::resolveMigrationAction($class)->upSql();

    if ($this->Database->driver()->transactionalDdl()) {
        $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
    } else {
        $this->Database->execute($sql);
    }
}

private function runDown(string $class): void
{
    $sql = self::resolveMigrationAction($class)->downSql();

    if ($this->Database->driver()->transactionalDdl()) {
        $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
    } else {
        $this->Database->execute($sql);
    }
}
```

## `ensureTable()` — Driver Passthrough

```php
public function ensureTable(): void
{
    $this->Database->execute(DdlBuilder::createTableSql(Migration::class, $this->Database->driver()));
}
```

## Docblock Update

Replace:
```
DDL note: ensureTable() and migration actions that run DDL
(CREATE TABLE, ALTER TABLE, etc.) cause MySQL to implicitly commit any open
transaction. Call ensureTable() before opening a transaction.
```

With:
```
Transaction behavior depends on the active driver:
- MySQL: DDL causes implicit commit; migrations run without wrapping.
- PostgreSQL/SQLite: DDL is transactional; Migrator wraps up/down in a transaction.
```

## Behavioral Differences by Driver

| Behavior | MySQL | PostgreSQL | SQLite |
|----------|-------|------------|--------|
| DDL in transaction | Implicit commit | Rolled back on failure | Rolled back on failure |
| Failed migration | Partial state possible | Clean rollback | Clean rollback |
| `#[RawSql]` with DDL | May trigger implicit commit | Wrapped in transaction | Wrapped in transaction |

## Unchanged

| What | Why |
|------|-----|
| `resolveMigrationAction()` | Dispatches on `#[MigrationAction]` marker — driver-agnostic |
| `migrate()` | Iterates status, calls `runUp()` — no driver knowledge needed |
| `rollback()` | Calls `runDown()` — no driver knowledge needed |
| `status()` | Delegates to `MigrationStatusResolver` — driver-agnostic |
| `create()` factory | Reads `#[MigrationsSource]` — driver-agnostic |
| `MigrationDiscovery` | Filesystem scanning |
| `MigrationStatusResolver` | Checksum comparison |
| All migration query classes | Quoting handled in Phase 3 via traits |

## Implementation Steps

1. Update `ensureTable()` — pass `$this->Database->driver()` to `DdlBuilder::createTableSql()`
2. Update `runUp()` — wrap in transaction when `transactionalDdl()` is true
3. Update `runDown()` — same pattern
4. Update class docblock
5. Run `./run fix:all`

## Files

| File | Change |
|------|--------|
| `src/Migrator.php` | `ensureTable()`, `runUp()`, `runDown()`, docblock |
