# RequireDownMigrationRector

Flags migration classes that carry `#[Migration]` but are missing a `down()` method.

**Category:** Migrations
**Mode:** `warn`
**Auto-fix:** No

## Rationale

Every migration must be reversible. Without `down()`, `./run migrate:rollback` will
instantiate the class and fail at runtime with a fatal error. Writing `down()` at the
same time as `up()` is far cheaper than reconstructing the rollback logic later.

## What It Detects

Any `class` node that has an attribute whose name ends with `Migration` (matching both
the short name `Migration` and the FQCN `ZeroToProd\Thryds\Attributes\Migration`) and
does not declare a method named `down`.

## In `warn` mode

```
// TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md
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
final class CreateUsersTable implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('CREATE TABLE users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)');
    }
}
```

### After

```php
// TODO: [RequireDownMigrationRector] Migration class is missing a down() method — add it to support rollback. See: utils/rector/docs/RequireDownMigrationRector.md
#[Migration(id: '0001', description: 'Create users table')]
final class CreateUsersTable implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('CREATE TABLE users (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY)');
    }
}
```

## Resolution

When you see the TODO comment from this rule:

1. Add a `down(Database $Database): void` method to the migration class.
2. Implement the inverse of `up()` — e.g. `DROP TABLE IF EXISTS users` for a `CREATE TABLE`.
3. Write `down()` defensively: assume partial DDL may have auto-committed, so use
   `IF EXISTS` guards rather than assuming the full `up()` ran successfully.
4. Run `./run check:all` to verify the TODO is gone.

## Related Rules

None.
