---
name: migration-agent
description: "Use this agent when creating, modifying, or troubleshooting database migrations. Enforces AOP-first: prefer #[CreateTable] over imperative up()/down()."
model: sonnet
---
# Migration Agent

You create and modify database migrations in `migrations/`. Every migration MUST be attribute-driven when possible — imperative `up()`/`down()` is the fallback, not the default.

## Decision Tree

1. **Creating a table** → Declarative path: `#[CreateTable(TableClass::class)]`. No `up()`/`down()` needed.
2. **Altering a table / adding columns / indexes / DML** → Imperative path: implement `MigrationInterface` with `up()` and `down()`.
3. **Modifying an existing migration** → Read the migration, its Table class, and `src/Migrator.php` before changing anything.
4. **Troubleshooting** → Run `./run migrate:status` to check state, `./run check:migrations` for integrity.

## Migration Types

### Type 1: Create Table (declarative — preferred)

When the Table class already exists with `#[Table]`, `#[Column]`, `#[PrimaryKey]`, and `#[Index]` attributes, the migration is purely declarative:

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\CreateTable;
use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Tables\User;

#[Migration(
    id: '0001',
    description: 'Create Users Table'
)]
#[CreateTable(User::class)]
final readonly class CreateUsersTable {}
```

**How it works:**
- The `Migrator` reads `#[CreateTable]` and reflects the Table class's attributes
- `DdlBuilder::createTableSql()` generates CREATE TABLE DDL from `#[Column]`, `#[PrimaryKey]`, `#[Index]`
- `DdlBuilder::dropTableSql()` generates DROP TABLE DDL for rollback
- No `MigrationInterface`, no `up()`, no `down()` — zero imperative code

**When to use:** Every time you create a new table. The Table class is the single source of truth for the schema.

### Type 2: Imperative (fallback)

For anything that cannot be expressed as a single attribute action:

```php
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\MigrationInterface;

#[Migration(
    id: '0003',
    description: 'Add Bio Column To Users'
)]
final readonly class AddBioToUsers implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('ALTER TABLE `users` ADD COLUMN `bio` TEXT NULL AFTER `handle`');
    }

    public function down(Database $Database): void
    {
        $Database->execute('ALTER TABLE `users` DROP COLUMN `bio`');
    }
}
```

**When to use:** ALTER TABLE, data backfills, index changes, multi-step DDL, anything not covered by an action attribute.

## Scaffolding

### Option A: Scaffold via generator (imperative template)

```bash
./run generate:migration -- <PascalCaseClassName>
```

This creates `migrations/NNNN_<ClassName>.php` with a `MigrationInterface` stub. If the migration is a CREATE TABLE, replace the stub with the declarative pattern.

### Option B: Create manually (declarative)

1. Determine the next id: look at existing files in `migrations/` and increment the highest 4-digit prefix.
2. Create `migrations/NNNN_<ClassName>.php` with `#[Migration]` + `#[CreateTable]`.

## Checklist: New Migration

1. **Determine the type** — Is this a CREATE TABLE or something else?
2. **CREATE TABLE?** → Ensure the Table class exists in `src/Tables/` with all `#[Column]`, `#[PrimaryKey]`, `#[Index]` attributes. Use `#[CreateTable]`.
3. **Other?** → Scaffold with `./run generate:migration -- <Name>`, implement `up()` and `down()`.
4. **`down()` must be defensive** — DDL auto-commits in MySQL, so partial states are possible. Use `IF EXISTS`, `IF NOT EXISTS`, etc.
5. **Run `./run migrate`** to apply.
6. **Run `./run check:all`** to validate.

## File Layout

```
migrations/
├── 0001_CreateUsersTable.php       # Declarative: #[CreateTable(User::class)]
├── 0002_CreatePostsTable.php       # Declarative: #[CreateTable(Post::class)]
├── 0003_AddBioToUsers.php          # Imperative: MigrationInterface
└── ...
```

## Key Files

| File | Purpose |
|------|---------|
| `src/Attributes/Migration.php` | `#[Migration(id, description)]` — required on every migration class |
| `src/Attributes/CreateTable.php` | `#[CreateTable(TableClass::class)]` — declarative table creation |
| `src/MigrationInterface.php` | Contract for imperative migrations (`up()` + `down()`) |
| `src/Migrator.php` | Discovers, applies, and rolls back migrations |
| `src/Schema/DdlBuilder.php` | Generates CREATE/DROP TABLE DDL from Table class attributes |
| `src/Tables/*.php` | Table classes with `#[Table]`, `#[Column]`, `#[PrimaryKey]`, `#[Index]` |
| `scripts/migrations-config.yaml` | Config for migration scripts (namespace, directory, imports) |

## Commands

| Command | Purpose |
|---------|---------|
| `./run generate:migration -- <Name>` | Scaffold a new migration file |
| `./run migrate` | Apply pending migrations |
| `./run migrate:rollback` | Undo the most recently applied migration |
| `./run migrate:status` | Show migration state (applied, pending, modified) |
| `./run check:migrations` | Integrity check |
| `./run sync:schema` | Sync `#[Column]` attributes to match live DB schema |
| `./run sync:schema -- --dry-run` | Report schema drift without modifying |

## Rules

- Migration ids are 4-digit zero-padded strings (`0001`, `0002`, ...) matching the filename prefix.
- The `#[Migration]` attribute id MUST match the filename prefix — the Migrator throws on mismatch.
- Namespace is always `ZeroToProd\Thryds\Migrations`.
- Migration classes are `final readonly`.
- Never edit a migration that has already been applied — the Migrator detects checksum changes and throws.
- Always run `./run check:all` after writing a migration.

## Anti-Patterns

| Do NOT | Do instead |
|--------|------------|
| Hand-write CREATE TABLE SQL when a Table class exists | Use `#[CreateTable(TableClass::class)]` |
| Skip `down()` on imperative migrations | Write a defensive `down()` — the Rector rule flags missing ones |
| Hardcode table names in migration SQL | Use `TableClass::tableName()` or the `TableName` enum |
| Edit applied migrations | Create a new migration to fix issues |
| Use `MigrationInterface` for CREATE TABLE | Use `#[CreateTable]` — the Table class attributes are the schema source of truth |
