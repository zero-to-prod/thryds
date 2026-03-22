# Phase 7: Migrator — Transactional DDL

## Goal

Make the `Migrator` aware of `Driver::transactionalDdl()` so that PostgreSQL and SQLite migrations are wrapped in transactions, while MySQL migrations continue to run without transaction wrapping (MySQL implicitly commits on DDL).

## Current State

```php
// src/Migrator.php — runUp()
private function runUp(string $class): void
{
    $action = self::resolveMigrationAction($class);

    if ($action !== null) {
        $this->Database->execute($action->upSql());
        return;
    }

    $this->instantiate($class)->up(Database: $this->Database);
}
```

No transaction wrapping. The docblock warns about MySQL implicit commits.

## Target State

### `runUp()` and `runDown()`

```php
private function runUp(string $class): void
{
    $action = self::resolveMigrationAction($class);
    $Driver = $this->Database->driver();

    if ($action !== null) {
        $sql = $action->upSql($Driver);
        if ($Driver->transactionalDdl()) {
            $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
        } else {
            $this->Database->execute($sql);
        }
        return;
    }

    if ($Driver->transactionalDdl()) {
        $this->Database->transaction(fn(Database $db) => $this->instantiate($class)->up(Database: $db));
    } else {
        $this->instantiate($class)->up(Database: $this->Database);
    }
}

private function runDown(string $class): void
{
    $action = self::resolveMigrationAction($class);
    $Driver = $this->Database->driver();

    if ($action !== null) {
        $sql = $action->downSql($Driver);
        if ($Driver->transactionalDdl()) {
            $this->Database->transaction(static fn(Database $db) => $db->execute($sql));
        } else {
            $this->Database->execute($sql);
        }
        return;
    }

    if ($Driver->transactionalDdl()) {
        $this->Database->transaction(fn(Database $db) => $this->instantiate($class)->down(Database: $db));
    } else {
        $this->instantiate($class)->down(Database: $this->Database);
    }
}
```

### `MigrationAction` Interface Update

From Phase 4, the interface already changed:

```php
interface MigrationAction
{
    public function upSql(Driver $Driver): string;
    public function downSql(Driver $Driver): string;
}
```

### `MigrationInterface` Docblock Update

```php
/**
 * Contract for all migration classes in migrations/.
 *
 * Both up() and down() receive the Database wrapper directly.
 * Transaction behavior depends on the active driver:
 * - MySQL: DDL causes implicit commit; migrations run without wrapping.
 * - PostgreSQL/SQLite: DDL is transactional; Migrator wraps up()/down() in a transaction.
 *
 * Write down() defensively (e.g. DROP TABLE IF EXISTS, IF NOT EXISTS guards)
 * to handle partial states on drivers without transactional DDL.
 */
interface MigrationInterface
{
    public function up(Database $Database): void;
    public function down(Database $Database): void;
}
```

### `ensureTable()` — Driver Passthrough

```php
public function ensureTable(): void
{
    $this->Database->execute(DdlBuilder::createTableSql(Migration::class, $this->Database->driver()));
}
```

## Behavioral Differences by Driver

| Behavior | MySQL | PostgreSQL | SQLite |
|----------|-------|------------|--------|
| DDL in transaction | Implicit commit | Fully transactional | Fully transactional |
| Failed migration | Partial state possible | Rolled back cleanly | Rolled back cleanly |
| `down()` defensiveness | Critical | Still good practice | Still good practice |
| `ensureTable()` timing | Before any transaction | Can be inside transaction | Can be inside transaction |

## Implementation Steps

### Step 1: Update `ensureTable()`

- Pass `$this->Database->driver()` to `DdlBuilder::createTableSql()`

### Step 2: Update `runUp()`

- Resolve `$Driver` from `$this->Database->driver()`
- Pass `$Driver` to `$action->upSql($Driver)` for attribute-driven migrations
- Wrap in `$this->Database->transaction()` when `$Driver->transactionalDdl()` is true

### Step 3: Update `runDown()`

- Same pattern as `runUp()`

### Step 4: Update `MigrationInterface` docblock

- Replace MySQL-specific implicit commit warning with driver-aware explanation

### Step 5: Run check:all

## Files Modified

| File | Change |
|------|--------|
| `src/Migrator.php` | `ensureTable()` passes Driver, `runUp()`/`runDown()` wrap in transactions per driver |
| `src/MigrationInterface.php` | Docblock updated for multi-driver awareness |
