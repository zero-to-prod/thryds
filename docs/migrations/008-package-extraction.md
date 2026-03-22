# Phase 8: Package Extraction — `zero-to-prod/db`

## Goal

Extract the database layer into a standalone Composer package at `zero-to-prod/db`. The package provides: connection management, attribute-driven query traits, DDL builder, migrator, and all database attributes. The consuming application (Thryds) requires the package and provides its own table models, query classes, and migrations.

## Package Structure

```
zero-to-prod/db/
├── composer.json
├── src/
│   ├── Database.php
│   ├── DatabaseConfig.php
│   ├── MigrationInterface.php
│   ├── MigrationStatus.php
│   ├── Migrator.php
│   ├── Attributes/
│   │   ├── AddColumn.php
│   │   ├── Column.php
│   │   ├── ConnectionOption.php
│   │   ├── CreateTable.php
│   │   ├── DeletesFrom.php
│   │   ├── DropColumn.php
│   │   ├── ForeignKey.php
│   │   ├── HasTableName.php
│   │   ├── Index.php
│   │   ├── InsertsInto.php
│   │   ├── Migration.php
│   │   ├── MigrationAction.php
│   │   ├── OnDelete.php
│   │   ├── OnUpdate.php
│   │   ├── PersistColumn.php
│   │   ├── PrimaryKey.php
│   │   ├── SchemaSync.php
│   │   ├── SelectsFrom.php
│   │   ├── Table.php
│   │   ├── Timezone.php
│   │   └── UpdatesIn.php
│   ├── Queries/
│   │   ├── DbCreate.php
│   │   ├── DbDelete.php
│   │   ├── DbRead.php
│   │   ├── DbUpdate.php
│   │   ├── Persist.php
│   │   ├── PersistResolver.php
│   │   └── Sql.php
│   └── Schema/
│       ├── Charset.php
│       ├── Collation.php
│       ├── DataType.php
│       ├── DdlBuilder.php
│       ├── Driver.php
│       ├── Engine.php
│       ├── ReferentialAction.php
│       └── SchemaSource.php
└── tests/
```

## Namespace

```
ZeroToProd\Db\
```

All classes move from `ZeroToProd\Thryds\*` to `ZeroToProd\Db\*`.

## composer.json

```json
{
    "name": "zero-to-prod/db",
    "description": "Attribute-driven database layer for PHP 8.5 — MySQL, PostgreSQL, SQLite via PDO",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=8.5",
        "ext-pdo": "*",
        "zero-to-prod/data-model": "^1.0"
    },
    "require-dev": {
        "phpunit/phpunit": "^12.0",
        "phpstan/phpstan": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "ZeroToProd\\Db\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "ZeroToProd\\Db\\Tests\\": "tests/"
        }
    }
}
```

## What Stays in Thryds

| File | Why |
|------|-----|
| `src/Tables/User.php` | App-specific table model |
| `src/Tables/UserColumns.php` | App-specific columns trait |
| `src/Tables/TableName.php` | App-specific enum of table names |
| `src/Queries/CreateUserQuery.php` | App-specific query |
| `src/Queries/FindUserByIdQuery.php` | App-specific query |
| `src/Queries/DeleteUserByIdQuery.php` | App-specific query |
| `src/Queries/UpdateUserQuery.php` | App-specific query |
| `src/Queries/InsertMigrationQuery.php` | App-specific — uses `DbCreate` trait from package |
| `src/Queries/DeleteMigrationQuery.php` | App-specific — uses `DbDelete` trait from package |
| `src/Queries/SelectMigrationsQuery.php` | App-specific — uses `DbRead` trait from package |
| `src/Queries/Resolvers/*.php` | App-specific persist resolvers |
| `migrations/*.php` | App-specific migration files |
| `src/Tables/Migration.php` | App-specific — defines the migration tracking table |

## Coupling Points to Sever

### 1. `db()` Global Function

**Problem:** Query traits call `db()` from Thryds' `functions.php`.

**Solution:** The package does NOT define `db()`. Query traits already accept an optional `?Database` parameter. The consuming app is responsible for providing either:
- A `db()` global function (current pattern)
- Dependency injection
- A service locator

The package requires that one of these is available. Document that `db()` must return a `Database` instance if the global function pattern is used.

```php
// In package: traits use this pattern
$db = $Database ?? db();

// The package does not define db(). The app must provide it.
// Example in app's functions.php:
function db(): \ZeroToProd\Db\Database
{
    return app()->database();
}
```

### 2. `Persist` Enum — Closed vs Open

**Problem:** `Persist` is a closed enum with app-specific cases (`random_id`, `password_hash`, `now`).

**Solution:** The package provides:
- `PersistResolver` interface (unchanged)
- `Persist` enum with the `#[ResolvesTo]` pattern

The cases `random_id`, `password_hash`, and `now` are general enough to ship with the package. They are not app-specific — they are common persistence hooks. The resolvers (`RandomIdResolver`, `PasswordHashResolver`, `NowResolver`) move to the package too.

If the app needs custom cases, it can create its own enum implementing the same pattern. The `#[PersistColumn]` attribute accepts `Persist` — to support app-defined enums, change it to accept `PersistResolver|Persist`. This is a future extension point, not required for extraction.

### 3. `TableName` Enum

**Problem:** `#[Table]` currently requires `TableName`, an app-specific enum.

**Solution:** Change `#[Table]` to accept `BackedEnum` instead of `TableName`:

```php
// Before
public function __construct(
    public TableName $TableName,
    ...
)

// After
public function __construct(
    public \BackedEnum $TableName,
    ...
)
```

The app defines its own `TableName` enum. The package is agnostic to which enum is used — it reads `->value` for the SQL table name.

### 4. `HasTableName` Trait

This trait reads `#[Table]` and returns `->TableName->value`. It works with any `BackedEnum` — no change needed beyond the `#[Table]` attribute itself.

### 5. `DataModel` Trait

Already an external dependency (`zero-to-prod/data-model`). No change.

### 6. App-Specific Attributes

These attributes are used by Thryds but are NOT part of the database layer:

| Attribute | Why it stays in Thryds |
|-----------|----------------------|
| `#[Infrastructure]` | Attribute graph metadata |
| `#[ClosedSet]` | Attribute graph metadata |
| `#[KeyRegistry]` | Attribute graph metadata |
| `#[KeySource]` | Attribute graph metadata |
| `#[Describe]` | DataModel configuration |
| `#[StubValue]` | Preload system |
| `#[Input]` | Form generation |
| `#[DataModel]` | Re-exported from `zero-to-prod/data-model` |

The package's attributes (`#[Column]`, `#[Table]`, etc.) must NOT import these Thryds-specific attributes. Currently some files use `#[Infrastructure]` — remove these from the package versions.

### 7. Migration Table Model

`Migration.php` (the table model for tracking migrations) defines the schema for the `migrations` table. It uses `#[Table]`, `#[Column]`, `#[PrimaryKey]` — all package attributes.

**Decision:** Ship `Migration.php` with the package. It is the migrator's own tracking table — not app-specific. The `TableName` reference changes to use the package's approach (a `BackedEnum` with `migrations` value).

### 8. Migration Query Classes

`InsertMigrationQuery`, `DeleteMigrationQuery`, `SelectMigrationsQuery` are used only by `Migrator`. They should ship with the package.

## Extraction Sequence

### Step 1: Create the package repository

- Initialize `zero-to-prod/db` repo
- Create `composer.json`
- Set up `src/` directory structure

### Step 2: Copy files with namespace changes

- Copy all files listed in the package structure above
- Find-and-replace `ZeroToProd\Thryds\` → `ZeroToProd\Db\` in package files
- Remove `#[Infrastructure]`, `#[ClosedSet]`, `#[KeyRegistry]` references from package files
- These are Thryds attribute-graph metadata, not database concerns

### Step 3: Update `#[Table]` to accept `BackedEnum`

- Change `TableName` type hint to `\BackedEnum`
- Ensure `HasTableName::tableName()` still works (reads `->value`)

### Step 4: Create package-internal `Migration` table model

- Ship `Migration.php` with the package
- Create a minimal `MigrationTableName` enum (or use a string constant) for the `migrations` table name
- Update migration query classes to reference the package-internal model

### Step 5: Verify `db()` is not defined in the package

- Grep for `function db()` — must not exist in package
- Traits use `$Database ?? db()` — the app provides `db()`

### Step 6: Update Thryds to require the package

```json
{
    "require": {
        "zero-to-prod/db": "^1.0"
    }
}
```

### Step 7: Update Thryds imports

- Find-and-replace `ZeroToProd\Thryds\Database` → `ZeroToProd\Db\Database`
- Same for all extracted classes
- Update `use` statements throughout Thryds

### Step 8: Delete extracted files from Thryds

- Remove all files that now live in the package
- Run `composer dump-autoload`

### Step 9: Run check:all in Thryds

## Files Moved to Package

| Thryds Path | Package Path |
|-------------|-------------|
| `src/Database.php` | `src/Database.php` |
| `src/DatabaseConfig.php` | `src/DatabaseConfig.php` |
| `src/MigrationInterface.php` | `src/MigrationInterface.php` |
| `src/MigrationStatus.php` | `src/MigrationStatus.php` |
| `src/Migrator.php` | `src/Migrator.php` |
| `src/Attributes/AddColumn.php` | `src/Attributes/AddColumn.php` |
| `src/Attributes/Column.php` | `src/Attributes/Column.php` |
| `src/Attributes/ConnectionOption.php` | `src/Attributes/ConnectionOption.php` |
| `src/Attributes/CreateTable.php` | `src/Attributes/CreateTable.php` |
| `src/Attributes/DeletesFrom.php` | `src/Attributes/DeletesFrom.php` |
| `src/Attributes/DropColumn.php` | `src/Attributes/DropColumn.php` |
| `src/Attributes/ForeignKey.php` | `src/Attributes/ForeignKey.php` |
| `src/Attributes/HasTableName.php` | `src/Attributes/HasTableName.php` |
| `src/Attributes/Index.php` | `src/Attributes/Index.php` |
| `src/Attributes/InsertsInto.php` | `src/Attributes/InsertsInto.php` |
| `src/Attributes/Migration.php` | `src/Attributes/Migration.php` |
| `src/Attributes/MigrationAction.php` | `src/Attributes/MigrationAction.php` |
| `src/Attributes/OnDelete.php` | `src/Attributes/OnDelete.php` |
| `src/Attributes/OnUpdate.php` | `src/Attributes/OnUpdate.php` |
| `src/Attributes/PersistColumn.php` | `src/Attributes/PersistColumn.php` |
| `src/Attributes/PrimaryKey.php` | `src/Attributes/PrimaryKey.php` |
| `src/Attributes/SchemaSync.php` | `src/Attributes/SchemaSync.php` |
| `src/Attributes/SelectsFrom.php` | `src/Attributes/SelectsFrom.php` |
| `src/Attributes/Table.php` | `src/Attributes/Table.php` |
| `src/Attributes/Timezone.php` | `src/Attributes/Timezone.php` |
| `src/Attributes/UpdatesIn.php` | `src/Attributes/UpdatesIn.php` |
| `src/Queries/DbCreate.php` | `src/Queries/DbCreate.php` |
| `src/Queries/DbDelete.php` | `src/Queries/DbDelete.php` |
| `src/Queries/DbRead.php` | `src/Queries/DbRead.php` |
| `src/Queries/DbUpdate.php` | `src/Queries/DbUpdate.php` |
| `src/Queries/Persist.php` | `src/Queries/Persist.php` |
| `src/Queries/PersistResolver.php` | `src/Queries/PersistResolver.php` |
| `src/Queries/Sql.php` | `src/Queries/Sql.php` |
| `src/Queries/Resolvers/RandomIdResolver.php` | `src/Queries/Resolvers/RandomIdResolver.php` |
| `src/Queries/Resolvers/PasswordHashResolver.php` | `src/Queries/Resolvers/PasswordHashResolver.php` |
| `src/Queries/Resolvers/NowResolver.php` | `src/Queries/Resolvers/NowResolver.php` |
| `src/Queries/InsertMigrationQuery.php` | `src/Queries/InsertMigrationQuery.php` |
| `src/Queries/DeleteMigrationQuery.php` | `src/Queries/DeleteMigrationQuery.php` |
| `src/Queries/SelectMigrationsQuery.php` | `src/Queries/SelectMigrationsQuery.php` |
| `src/Schema/Charset.php` | `src/Schema/Charset.php` |
| `src/Schema/Collation.php` | `src/Schema/Collation.php` |
| `src/Schema/DataType.php` | `src/Schema/DataType.php` |
| `src/Schema/DdlBuilder.php` | `src/Schema/DdlBuilder.php` |
| `src/Schema/Driver.php` | `src/Schema/Driver.php` |
| `src/Schema/Engine.php` | `src/Schema/Engine.php` |
| `src/Schema/ReferentialAction.php` | `src/Schema/ReferentialAction.php` |
| `src/Schema/SchemaSource.php` | `src/Schema/SchemaSource.php` |
| `src/Tables/Migration.php` | `src/Tables/Migration.php` |
