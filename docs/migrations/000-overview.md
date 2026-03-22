# Database Package Extraction — Migration Plan

## Goal

Extract the database layer from `ZeroToProd\Thryds` into a standalone Composer package (`zero-to-prod/db`) that supports MySQL, PostgreSQL, and SQLite through a single `#[Driver]` attribute. Everything else aligns to that declaration.

## Organizing Principles Applied

- **Constants name things** — `Driver` enum cases name the supported databases
- **Enumerations define sets** — `Driver` is the closed set of supported database backends
- **PHP Attributes define properties** — `#[Driver]` on the `Database` class declares the active backend; all downstream behavior resolves from it

## Design Thesis

One attribute. One enum. Everything aligns.

```php
#[Driver(Driver::pgsql)]
class Database { ... }
```

The `Driver` enum is the single source of dialect knowledge. It owns: identifier quoting, DSN format, type mapping, DDL syntax, timezone commands, reconnect patterns, auto-increment keywords, and transactional DDL awareness. No dialect interface, no strategy classes, no inheritance — one enum with methods.

## Migration Phases

| Phase | Document | Summary |
|-------|----------|---------|
| 1 | [001-driver-enum.md](001-driver-enum.md) | Create `Driver` enum with all dialect methods |
| 2 | [002-database-config.md](002-database-config.md) | Make `DatabaseConfig` driver-aware |
| 3 | [003-database-class.md](003-database-class.md) | Refactor `Database` to read `#[Driver]` and delegate |
| 4 | [004-ddl-builder.md](004-ddl-builder.md) | Refactor `DdlBuilder` to delegate to `Driver` |
| 5 | [005-table-attribute.md](005-table-attribute.md) | Make `Engine`, `Charset`, `Collation` optional on `#[Table]` |
| 6 | [006-query-traits.md](006-query-traits.md) | Update query traits for driver-aware quoting |
| 7 | [007-migrator.md](007-migrator.md) | Add transactional DDL support to `Migrator` |
| 8 | [008-package-extraction.md](008-package-extraction.md) | Extract into `zero-to-prod/db` Composer package |
| 9 | [009-testing.md](009-testing.md) | Multi-driver test strategy |

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
Phase 1 (Driver enum)
  └─→ Phase 2 (DatabaseConfig)
  └─→ Phase 3 (Database class)
  └─→ Phase 4 (DdlBuilder)
       └─→ Phase 5 (#[Table] attribute)
  └─→ Phase 6 (Query traits)
  └─→ Phase 7 (Migrator)
       └─→ Phase 8 (Package extraction)
            └─→ Phase 9 (Testing)
```

Phases 2-7 all depend on Phase 1 but are independent of each other. Phase 8 depends on all prior phases. Phase 9 runs in parallel with Phase 8.

## Files Overview

### New Files

| File | Type | Phase |
|------|------|-------|
| `src/Schema/Driver.php` | Enum | 1 |

### Modified Files

| File | Change | Phase |
|------|--------|-------|
| `src/DatabaseConfig.php` | Accept `Driver`, delegate DSN to `Driver::dsn()` | 2 |
| `src/Database.php` | Read `#[Driver]`, delegate timezone and reconnect to `Driver` | 3 |
| `src/Schema/DdlBuilder.php` | Accept `Driver`, delegate quoting/types/DDL to `Driver` methods | 4 |
| `src/Attributes/Table.php` | Make `Engine`, `Charset`, `Collation` nullable | 5 |
| `src/Attributes/HasTableName.php` | No change — reads `#[Table]` which still exists | — |
| `src/Attributes/CreateTable.php` | Pass `Driver` to `DdlBuilder` | 4 |
| `src/Attributes/AddColumn.php` | Pass `Driver` to `DdlBuilder` | 4 |
| `src/Attributes/Column.php` | No structural change — `unsigned` and `auto_increment` become advisory | 4 |
| `src/Queries/DbCreate.php` | Use `Driver::quote()` for identifiers | 6 |
| `src/Queries/DbRead.php` | Use `Driver::quote()` for identifiers | 6 |
| `src/Queries/DbDelete.php` | Use `Driver::quote()` for identifiers | 6 |
| `src/Queries/DbUpdate.php` | Use `Driver::quote()` for identifiers | 6 |
| `src/Migrator.php` | Wrap DDL in transactions when `Driver::transactionalDdl()` is true | 7 |
| `src/MigrationInterface.php` | Docblock update — remove MySQL-specific implicit commit note | 7 |
| `src/Schema/Engine.php` | Docblock update — note MySQL/MariaDB only | 5 |
| `src/Schema/Charset.php` | Docblock update — note MySQL/MariaDB only | 5 |
| `src/Schema/Collation.php` | Docblock update — note MySQL/MariaDB only | 5 |
| `src/Tables/Migration.php` | Make `Engine`/`Charset`/`Collation` nullable on `#[Table]` | 5 |
| `src/Tables/User.php` | Make `Engine`/`Charset`/`Collation` nullable on `#[Table]` | 5 |

### Removed Attributes

| Attribute | Reason | Phase |
|-----------|--------|-------|
| `#[ReconnectOn]` | Reconnect patterns move to `Driver::reconnectPatterns()` | 3 |

### Unchanged Files

| File | Why |
|------|-----|
| `src/Attributes/Column.php` | Declares intent; `Driver` interprets `unsigned`/`auto_increment` |
| `src/Attributes/PrimaryKey.php` | Standard SQL concept |
| `src/Attributes/Index.php` | Standard SQL concept |
| `src/Attributes/ForeignKey.php` | Standard SQL concept |
| `src/Attributes/OnDelete.php` | Standard SQL concept |
| `src/Attributes/OnUpdate.php` | Standard SQL concept |
| `src/Attributes/InsertsInto.php` | Driver-agnostic |
| `src/Attributes/SelectsFrom.php` | Driver-agnostic |
| `src/Attributes/DeletesFrom.php` | Driver-agnostic |
| `src/Attributes/UpdatesIn.php` | Driver-agnostic |
| `src/Attributes/PersistColumn.php` | Application logic, not SQL |
| `src/Attributes/Migration.php` | Metadata, not SQL |
| `src/Attributes/MigrationAction.php` | Interface — unchanged |
| `src/Attributes/SchemaSync.php` | Sync direction, not SQL |
| `src/Queries/Persist.php` | Application logic |
| `src/Queries/PersistResolver.php` | Application logic |
| `src/Queries/Resolvers/*.php` | Application logic |
| `src/Queries/Sql.php` | Standard SQL fragments |
| `src/Schema/DataType.php` | Declares types; `Driver` maps them |
| `src/Schema/SchemaSource.php` | Sync direction |
| `src/Schema/ReferentialAction.php` | Standard SQL |
