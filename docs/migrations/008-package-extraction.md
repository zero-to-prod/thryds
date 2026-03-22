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
│   ├── MigrationDiscovery.php
│   ├── MigrationStatusResolver.php
│   ├── MigrationStatusRow.php
│   ├── MigrationStatus.php
│   ├── Migrator.php
│   ├── RowAccess.php
│   ├── Attributes/
│   │   ├── AddColumn.php
│   │   ├── Column.php
│   │   ├── Connection.php
│   │   ├── ConnectionOption.php
│   │   ├── CreateTable.php
│   │   ├── DeletesFrom.php
│   │   ├── DropColumn.php
│   │   ├── EnvVar.php
│   │   ├── ForeignKey.php
│   │   ├── HasTableName.php
│   │   ├── Index.php
│   │   ├── InsertsInto.php
│   │   ├── Migration.php
│   │   ├── MigrationAction.php
│   │   ├── MigrationsSource.php
│   │   ├── OnDelete.php
│   │   ├── OnUpdate.php
│   │   ├── PersistColumn.php
│   │   ├── PrimaryKey.php
│   │   ├── RawSql.php
│   │   ├── ResolvesTo.php
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
│   │   ├── DeleteMigrationQuery.php
│   │   ├── InsertMigrationQuery.php
│   │   ├── Persist.php
│   │   ├── PersistResolver.php
│   │   ├── SelectLastMigrationQuery.php
│   │   ├── SelectMigrationsQuery.php
│   │   ├── Sql.php
│   │   └── Resolvers/
│   │       ├── NowResolver.php
│   │       ├── PasswordHashResolver.php
│   │       └── RandomIdResolver.php
│   ├── Schema/
│   │   ├── Charset.php
│   │   ├── Collation.php
│   │   ├── DataType.php
│   │   ├── DdlBuilder.php
│   │   ├── Driver.php
│   │   ├── Engine.php
│   │   ├── ReferentialAction.php
│   │   ├── SchemaSource.php
│   │   └── SortDirection.php
│   └── Tables/
│       ├── Migration.php
│       └── MigrationColumns.php
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
| `migrations/*.php` | App-specific migration files |

## Coupling Points to Sever

### 1. `#[Connection]` and `app()` Dependency

**Problem:** `Connection::resolve()` calls `app()->make()` to resolve the `Database` instance from a container:

```php
public static function resolve(string $class): Database
{
    $attrs = new ReflectionClass($class)->getAttributes(self::class);
    return app()->make($attrs !== [] ? $attrs[0]->newInstance()->database : Database::class);
}
```

`app()` is a Thryds global function — the package cannot depend on it.

**Solution:** The package defines `Connection` with a pluggable resolver. The consuming app registers the resolver at boot:

```php
// Package — Connection attribute
#[Attribute(Attribute::TARGET_CLASS)]
readonly class Connection
{
    /** @var null|Closure(class-string): Database */
    private static ?Closure $resolver = null;

    public function __construct(public string $database) {}

    public static function setResolver(Closure $resolver): void
    {
        self::$resolver = $resolver;
    }

    public static function resolve(string $class): Database
    {
        if (self::$resolver === null) {
            throw new RuntimeException('Connection resolver not configured. Call Connection::setResolver() at boot.');
        }

        $attrs = new ReflectionClass($class)->getAttributes(self::class);
        $db_class = $attrs !== [] ? $attrs[0]->newInstance()->database : Database::class;

        return (self::$resolver)($db_class);
    }
}

// App bootstrap (Thryds)
Connection::setResolver(static fn(string $class) => app()->make($class));
```

### 2. `TableName` Enum

**Problem:** `#[Table]` requires `TableName`, an app-specific enum.

**Solution:** Change `#[Table]` to accept `BackedEnum`:

```php
// Before
public function __construct(public TableName $TableName, ...)

// After
public function __construct(public \BackedEnum $TableName, ...)
```

The app defines its own `TableName` enum. The package reads `->value`.

### 3. `#[MigrationsSource]` on Migrator

**Problem:** The attribute hardcodes Thryds-specific values:

```php
#[MigrationsSource(
    directory: 'migrations',
    namespace: 'ZeroToProd\\Thryds\\Migrations',
)]
```

**Solution:** The package ships `Migrator` without `#[MigrationsSource]`. The app subclasses or decorates:

Option A — App puts `#[MigrationsSource]` on its own class:
```php
// In package: Migrator::create() reads #[MigrationsSource] from static::class
// In app: extend or configure via the attribute on a project-level class
```

Option B — The factory accepts parameters directly (already supported via constructor). `Migrator::create()` is a convenience that reads from the attribute. The app can use either path.

### 4. App-Specific Attributes to Strip

These Thryds-specific attributes appear on package files and must be removed:

| Attribute | Where used | Action |
|-----------|-----------|--------|
| `#[Infrastructure]` | On most classes/traits | Remove from package files |
| `#[ClosedSet]` | On enums (`DataType`, `Engine`, etc.) | Remove from package files |
| `#[KeyRegistry]` / `#[KeySource]` | On `Sql`, `Migrator` | Remove from package files |
| `#[Describe]` | On `DatabaseConfig`, `MigrationStatusRow`, `MigrationColumns` | Keep — comes from `zero-to-prod/data-model` |
| `#[DataModel]` | On table/DTO classes | Keep — comes from `zero-to-prod/data-model` |

### 5. `Migration` Table Model and Queries

The `Migration` table, `MigrationColumns` trait, and migration query classes (`InsertMigrationQuery`, `DeleteMigrationQuery`, `SelectMigrationsQuery`, `SelectLastMigrationQuery`) are internal to the migrator. They ship with the package.

The `#[Connection(database: Database::class)]` on `Migration.php` stays — it uses the base `Database` class which is the package's own type.

The `TableName::migrations` reference changes. The package provides its own internal enum or the `#[Table]` attribute accepts `BackedEnum` (solution from point 2).

### 6. `Persist` Enum and Resolvers

The `Persist` enum, `PersistResolver` marker, `ResolvesTo` attribute, and the three resolvers (`RandomIdResolver`, `PasswordHashResolver`, `NowResolver`) are general-purpose. They ship with the package.

`#[ClosedSet]` and `Domain::*` references on `Persist` are stripped (Thryds-specific graph metadata).

### 7. `SortDirection` Enum

Ships with the package — used by `SelectsFrom` attribute. `#[ClosedSet]` reference stripped.

## Extraction Sequence

### Step 1: Create the package repository

- Initialize `zero-to-prod/db` repo
- Create `composer.json`
- Set up `src/` directory structure

### Step 2: Copy files with namespace changes

- Copy all files listed in the package structure
- Find-and-replace `ZeroToProd\Thryds\` → `ZeroToProd\Db\` in package files
- Remove `#[Infrastructure]`, `#[ClosedSet]`, `#[KeyRegistry]`, `#[KeySource]` and their imports
- Remove `Domain::*` references

### Step 3: Update `#[Table]` to accept `BackedEnum`

- Change `TableName` type hint to `\BackedEnum`
- `HasTableName::tableName()` still reads `->value` — works unchanged

### Step 4: Refactor `Connection::resolve()` to use pluggable resolver

- Add static `$resolver` property and `setResolver()` method
- `resolve()` calls the resolver closure instead of `app()->make()`
- Remove `use function app` import

### Step 5: Create package-internal table name handling

- The `Migration` table model needs a `BackedEnum` for its table name
- Ship a minimal internal enum or use the existing `TableName` pattern
- Migration query classes reference the package-internal model

### Step 6: Verify no Thryds dependencies remain

- Grep for `ZeroToProd\Thryds` — must not exist in package
- Grep for `app()` — must not exist in package
- Grep for `#[Infrastructure]`, `#[ClosedSet]`, `#[KeyRegistry]` — must not exist

### Step 7: Update Thryds to require the package

```json
{
    "require": {
        "zero-to-prod/db": "^1.0"
    }
}
```

### Step 8: Update Thryds imports

- Find-and-replace `ZeroToProd\Thryds\Database` → `ZeroToProd\Db\Database` (etc.)
- Update `use` statements throughout Thryds
- Add `Connection::setResolver()` call to app bootstrap

### Step 9: Delete extracted files from Thryds

- Remove all files that now live in the package
- Run `composer dump-autoload`

### Step 10: Run check:all in Thryds

## Files Moved to Package

| Thryds Path | Package Path |
|-------------|-------------|
| `src/Database.php` | `src/Database.php` |
| `src/DatabaseConfig.php` | `src/DatabaseConfig.php` |
| `src/MigrationDiscovery.php` | `src/MigrationDiscovery.php` |
| `src/MigrationStatus.php` | `src/MigrationStatus.php` |
| `src/MigrationStatusResolver.php` | `src/MigrationStatusResolver.php` |
| `src/MigrationStatusRow.php` | `src/MigrationStatusRow.php` |
| `src/Migrator.php` | `src/Migrator.php` |
| `src/RowAccess.php` | `src/RowAccess.php` |
| `src/Attributes/AddColumn.php` | `src/Attributes/AddColumn.php` |
| `src/Attributes/Column.php` | `src/Attributes/Column.php` |
| `src/Attributes/Connection.php` | `src/Attributes/Connection.php` |
| `src/Attributes/ConnectionOption.php` | `src/Attributes/ConnectionOption.php` |
| `src/Attributes/CreateTable.php` | `src/Attributes/CreateTable.php` |
| `src/Attributes/DeletesFrom.php` | `src/Attributes/DeletesFrom.php` |
| `src/Attributes/DropColumn.php` | `src/Attributes/DropColumn.php` |
| `src/Attributes/EnvVar.php` | `src/Attributes/EnvVar.php` |
| `src/Attributes/ForeignKey.php` | `src/Attributes/ForeignKey.php` |
| `src/Attributes/HasTableName.php` | `src/Attributes/HasTableName.php` |
| `src/Attributes/Index.php` | `src/Attributes/Index.php` |
| `src/Attributes/InsertsInto.php` | `src/Attributes/InsertsInto.php` |
| `src/Attributes/Migration.php` | `src/Attributes/Migration.php` |
| `src/Attributes/MigrationAction.php` | `src/Attributes/MigrationAction.php` |
| `src/Attributes/MigrationsSource.php` | `src/Attributes/MigrationsSource.php` |
| `src/Attributes/OnDelete.php` | `src/Attributes/OnDelete.php` |
| `src/Attributes/OnUpdate.php` | `src/Attributes/OnUpdate.php` |
| `src/Attributes/PersistColumn.php` | `src/Attributes/PersistColumn.php` |
| `src/Attributes/PrimaryKey.php` | `src/Attributes/PrimaryKey.php` |
| `src/Attributes/RawSql.php` | `src/Attributes/RawSql.php` |
| `src/Attributes/ResolvesTo.php` | `src/Attributes/ResolvesTo.php` |
| `src/Attributes/SchemaSync.php` | `src/Attributes/SchemaSync.php` |
| `src/Attributes/SelectsFrom.php` | `src/Attributes/SelectsFrom.php` |
| `src/Attributes/Table.php` | `src/Attributes/Table.php` |
| `src/Attributes/Timezone.php` | `src/Attributes/Timezone.php` |
| `src/Attributes/UpdatesIn.php` | `src/Attributes/UpdatesIn.php` |
| `src/Queries/DbCreate.php` | `src/Queries/DbCreate.php` |
| `src/Queries/DbDelete.php` | `src/Queries/DbDelete.php` |
| `src/Queries/DbRead.php` | `src/Queries/DbRead.php` |
| `src/Queries/DbUpdate.php` | `src/Queries/DbUpdate.php` |
| `src/Queries/DeleteMigrationQuery.php` | `src/Queries/DeleteMigrationQuery.php` |
| `src/Queries/InsertMigrationQuery.php` | `src/Queries/InsertMigrationQuery.php` |
| `src/Queries/Persist.php` | `src/Queries/Persist.php` |
| `src/Queries/PersistResolver.php` | `src/Queries/PersistResolver.php` |
| `src/Queries/SelectLastMigrationQuery.php` | `src/Queries/SelectLastMigrationQuery.php` |
| `src/Queries/SelectMigrationsQuery.php` | `src/Queries/SelectMigrationsQuery.php` |
| `src/Queries/Sql.php` | `src/Queries/Sql.php` |
| `src/Queries/Resolvers/NowResolver.php` | `src/Queries/Resolvers/NowResolver.php` |
| `src/Queries/Resolvers/PasswordHashResolver.php` | `src/Queries/Resolvers/PasswordHashResolver.php` |
| `src/Queries/Resolvers/RandomIdResolver.php` | `src/Queries/Resolvers/RandomIdResolver.php` |
| `src/Schema/Charset.php` | `src/Schema/Charset.php` |
| `src/Schema/Collation.php` | `src/Schema/Collation.php` |
| `src/Schema/DataType.php` | `src/Schema/DataType.php` |
| `src/Schema/DdlBuilder.php` | `src/Schema/DdlBuilder.php` |
| `src/Schema/Driver.php` | `src/Schema/Driver.php` |
| `src/Schema/Engine.php` | `src/Schema/Engine.php` |
| `src/Schema/ReferentialAction.php` | `src/Schema/ReferentialAction.php` |
| `src/Schema/SchemaSource.php` | `src/Schema/SchemaSource.php` |
| `src/Schema/SortDirection.php` | `src/Schema/SortDirection.php` |
| `src/Tables/Migration.php` | `src/Tables/Migration.php` |
| `src/Tables/MigrationColumns.php` | `src/Tables/MigrationColumns.php` |
