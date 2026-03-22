# RequireDownMigrationRector

Flags migration classes that carry `#[Migration]` but have no `MigrationAction` attribute.

**Category:** Migrations
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Every migration must declare its behavior via a `MigrationAction` attribute
(`#[CreateTable]`, `#[AddColumn]`, `#[DropColumn]`, or `#[RawSql]`). Without one,
the `Migrator` throws a `RuntimeException` at runtime. This rule provides faster
feedback during static analysis.

## What It Detects

Any `class` node that has an attribute whose name ends with `Migration` (matching both
the short name `Migration` and the FQCN `ZeroToProd\Thryds\Attributes\Migration`) and
does not have an attribute whose name ends with `CreateTable`, `AddColumn`, `DropColumn`,
or `RawSql`.

## In `warn` mode

```
// TODO: [RequireDownMigrationRector] Migration class has no MigrationAction attribute — add #[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql]. See: utils/rector/docs/RequireDownMigrationRector.md
```

The comment is prepended to the class declaration and is idempotent — re-running Rector
will not add a second comment if one already exists.

## Configuration

| Option    | Type     | Default  | Description                                      |
|-----------|----------|----------|--------------------------------------------------|
| `mode`    | `string` | `'warn'` | Only `'warn'` is supported — `'auto'` is a no-op |
| `message` | `string` | see above | The TODO comment text                           |

## Example

### Before

```php
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable {}
```

### After

```php
// TODO: [RequireDownMigrationRector] Migration class has no MigrationAction attribute — add #[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql]. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable {}
```

## Resolution

When you see the TODO comment from this rule:

1. Add a `MigrationAction` attribute to the migration class.
2. For table creation, use `#[CreateTable(TableClass::class)]`.
3. For column changes, use `#[AddColumn]` or `#[DropColumn]`.
4. For arbitrary SQL, use `#[RawSql(up: '...', down: '...')]`. Write `down` SQL
   defensively: assume partial DDL may have auto-committed, so use `IF EXISTS` guards.
5. Run `./run check:all` to verify the TODO is gone.

## Related Rules

None.
