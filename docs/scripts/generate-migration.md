# Fix: generate-migration.php — Externalize Hardcoded Namespace and Template

## Script

`scripts/generate-migration.php`

## Violations

### 1. Hardcoded namespace (line 61)

```php
namespace ZeroToProd\Thryds\Migrations;
```

### 2. Hardcoded class references (lines 63-65)

```php
use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\MigrationInterface;
```

### 3. Hardcoded migrations directory (line 38, 46)

```php
$existing = glob($base_dir . '/migrations/[0-9][0-9][0-9][0-9]_*.php') ?: [];
$filename = "migrations/{$next_id}_{$class_name}.php";
```

## Fix

1. Create `migrations-config.yaml` at the project root:

```yaml
directory: migrations
namespace: ZeroToProd\Thryds\Migrations
imports:
  - ZeroToProd\Thryds\Attributes\Migration
  - ZeroToProd\Thryds\Database
  - ZeroToProd\Thryds\MigrationInterface
interface: MigrationInterface
attribute: Migration
```

2. In the script, load the config and use its values to build the template string dynamically.

3. The same `migrations-config.yaml` should be shared with `check-migrations.php`, `migrate.php`, `migrate-status.php`, and `migrate-rollback.php`.

## Constraints

- The generated migration file must have the same structure as today.
- The `id` auto-increment logic is generic and can stay as-is.
- Do not add the autoloader — this script does not currently require it.
- Run `./run check:all` to verify no regressions.
