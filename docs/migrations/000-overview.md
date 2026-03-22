# Database Package Extraction — Migration Plan

## Goal

Extract the database layer from `ZeroToProd\Thryds` into a standalone Composer package (`zero-to-prod/db`) that supports MySQL, PostgreSQL, and SQLite. The `Driver` enum is the single source of dialect knowledge. The attribute graph is the wiring — no parameter threading, no new attribute classes.

## Organizing Principles Applied

- **Constants name things** — `Driver` enum cases name the supported databases
- **Enumerations define sets** — `Driver` is the closed set of supported database backends
- **PHP Attributes define properties** — `#[Connection]` on table classes declares the database; `#[EnvVar]` on `DatabaseConfig` declares the driver. The attribute graph resolves everything.

## Design Thesis

The attribute graph already knows the driver. Don't thread it — resolve it.

```
#[CreateTable(User::class)]
    → User
        → #[Connection(database: Database::class)]
            → Database → DatabaseConfig → Driver
```

Migration actions resolve `Driver` from the table class they operate on. Query traits resolve it via `Connection::resolve()`. The Migrator reads it from `$this->Database->driver()`. No parameter threading. No new attributes. One enum with methods.

`Driver` is runtime configuration (dev=sqlite, prod=mysql), not a compile-time declaration. It lives in `DatabaseConfig` via `#[EnvVar(Env::DB_DRIVER)]`, not as a class-level attribute.

## Migration Phases

| Phase | Document | Summary |
|-------|----------|---------|
| 1 | [001-driver-and-config.md](001-driver-and-config.md) | Create `Driver` enum, wire into `DatabaseConfig` and `Database` |
| 2 | [002-ddl-and-table.md](002-ddl-and-table.md) | Make `DdlBuilder` driver-aware, `#[Table]` params optional, migration actions self-resolve |
| 3 | [003-query-traits.md](003-query-traits.md) | Driver-aware identifier quoting in all four query traits |
| 4 | [004-migrator.md](004-migrator.md) | Transactional DDL wrapping |
| 5 | [005-package-and-testing.md](005-package-and-testing.md) | Extract `zero-to-prod/db`, multi-driver test suite |

## Scope Boundary

This plan covers:
- The `Driver` enum and all dialect behavior it owns
- Refactoring every MySQL-specific touchpoint to delegate to `Driver`
- Making MySQL-only concepts (`Engine`, `Charset`, `Collation`) optional
- Transactional DDL awareness in `Migrator`
- Extracting the database layer into a standalone package
- Testing strategy for three database backends

This plan does not cover:
- Driver-specific features not in the current codebase (e.g., PostgreSQL arrays, SQLite WAL mode)
- Database-specific migration attributes beyond what exists today
- ORM or active record patterns
- Connection pooling or read replicas

## Dependency Order

```
Phase 1 (Driver enum + config + Database)
  └─→ Phase 2 (DdlBuilder + #[Table] + migration actions)
  └─→ Phase 3 (Query traits)
  └─→ Phase 4 (Migrator)
       └─→ Phase 5 (Package extraction + testing)
```

Phases 2, 3, 4 all depend on Phase 1 but are independent of each other. Phase 5 depends on all prior phases.

## Resolution Chain

An agent follows one path from any database operation to the active driver:

```
Query class
  → #[InsertsInto(User::class)] or #[SelectsFrom(User::class)]
    → User
      → #[Connection(database: Database::class)]
        → Database
          → DatabaseConfig
            → #[EnvVar(Env::DB_DRIVER)] → Driver::mysql
```

Everything is traversable from attributes. The only imperative wiring is `Connection::setResolver()` at app boot — one line.

## Key Design Decisions

### Driver is never a parameter on migration actions

`upSql()` / `downSql()` signatures don't change. `CreateTable`, `AddColumn`, `DropColumn` resolve `Driver` internally from `#[Connection]` on their table class. `RawSql` doesn't need a driver — it's consumer-authored SQL. The `#[MigrationAction]` duck-type contract stays as-is.

### DdlBuilder keeps `Driver` as a parameter

`DdlBuilder` is a pure static utility: (class + Driver) → SQL string. Keeping `Driver` explicit makes it testable without a container. The callers (migration actions) resolve `Driver` and pass it.

### No `#[Driver]` attribute class

`Driver` is runtime config, not a compile-time declaration. It lives in `DatabaseConfig` as a property populated by `#[EnvVar(Env::DB_DRIVER)]`. `Database` exposes it via `driver()`.

### `Connection::setResolver()` is the single boot-time wiring

The package defines `Connection` with a pluggable resolver. The app registers it once:

```php
Connection::setResolver(static fn(string $class) => app()->make($class));
```

## Files Overview

### New Files

| File | Type | Phase |
|------|------|-------|
| `src/Schema/Driver.php` | Enum | 1 |

### Modified Files

| File | Change | Phase |
|------|--------|-------|
| `src/DatabaseConfig.php` | Add `$driver` with `#[EnvVar]`, delegate DSN/port to `Driver` | 1 |
| `src/Database.php` | Add `driver()`, delegate timezone/reconnect to `Driver`, remove `#[ReconnectOn]` | 1 |
| `src/Schema/DdlBuilder.php` | Accept `Driver` param, delegate quoting/types/table options | 2 |
| `src/Attributes/Table.php` | Make `Engine`, `Charset`, `Collation` nullable | 2 |
| `src/Attributes/CreateTable.php` | Resolve `Driver` from `#[Connection]` on table class, pass to `DdlBuilder` | 2 |
| `src/Attributes/AddColumn.php` | Same — self-resolve `Driver` | 2 |
| `src/Attributes/DropColumn.php` | Same — self-resolve `Driver`, guard SQLite | 2 |
| `src/Queries/DbCreate.php` | Quote identifiers via `Driver::quote()` | 3 |
| `src/Queries/DbRead.php` | Quote identifiers via `Driver::quote()` | 3 |
| `src/Queries/DbDelete.php` | Quote identifiers via `Driver::quote()` | 3 |
| `src/Queries/DbUpdate.php` | Quote identifiers via `Driver::quote()` | 3 |
| `src/Migrator.php` | Transactional DDL wrapping via `$this->Database->driver()` | 4 |
| `src/Schema/Engine.php` | Docblock — note MySQL/MariaDB only | 2 |
| `src/Schema/Charset.php` | Docblock — note MySQL/MariaDB only | 2 |
| `src/Schema/Collation.php` | Docblock — note MySQL/MariaDB only | 2 |
| `src/Schema/SortDirection.php` | Docblock — remove MySQL-specific note | 2 |

### Removed

| What | Why | Phase |
|------|-----|-------|
| `#[ReconnectOn]` attributes on `Database` | Reconnect patterns move to `Driver::reconnectPatterns()` | 1 |
| `$reconnect_messages` static cache | No longer needed — `Driver` method is pure | 1 |
| `resolveReconnectMessages()` method | Replaced by `Driver::reconnectPatterns()` | 1 |
| `DdlBuilder::columnTypeSql()` | Logic moves to `Driver::typeSql()` | 2 |
| `DdlBuilder::ALTER_TABLE` constant | Contained hardcoded backtick | 2 |

### Unchanged

| File | Why |
|------|-----|
| `src/Attributes/MigrationAction.php` | Marker attribute — no contract change |
| `src/Attributes/RawSql.php` | `upSql()` / `downSql()` unchanged — consumer-authored SQL |
| `src/Attributes/Connection.php` | Already abstracts DB resolution |
| `src/Attributes/Column.php` | Declares intent; `Driver` interprets |
| `src/Attributes/SelectsFrom.php` | Driver-agnostic (SortDirection, limit, offset are standard SQL) |
| `src/Attributes/PrimaryKey.php`, `Index.php`, `ForeignKey.php` | Standard SQL concepts |
| `src/Attributes/InsertsInto.php`, `DeletesFrom.php`, `UpdatesIn.php` | Driver-agnostic |
| `src/Attributes/PersistColumn.php`, `Migration.php`, `EnvVar.php` | Not SQL |
| `src/Attributes/MigrationsSource.php`, `SchemaSync.php`, `ResolvesTo.php` | Not SQL |
| `src/Queries/Persist.php`, `PersistResolver.php`, `Resolvers/*.php` | Application logic |
| `src/Queries/Sql.php` | Standard SQL fragments |
| `src/MigrationDiscovery.php` | Filesystem scanning — driver-agnostic |
| `src/MigrationStatusResolver.php` | Status computation — driver-agnostic |
| `src/MigrationStatusRow.php` | Typed DTO — driver-agnostic |
| `src/RowAccess.php` | Type-narrowing — driver-agnostic |
| `src/Schema/DataType.php` | Declares types; `Driver` maps them |
| `src/Schema/SchemaSource.php`, `ReferentialAction.php`, `SortDirection.php` | Standard SQL |

## Adding a New Driver

An agent reads the `#[ClosedSet]` `addCase` instructions on `Driver`:

1. Add case to `Driver` enum
2. Handle every `match()` arm (DSN, quoting, type mapping, timezone, reconnect, auto-increment, table options, transactional DDL, enum constraint, default port)
3. Add to test matrix

One file. One enum.
