# Fix: check-migrations.php — Externalize Hardcoded Namespace and Path

## Script

`scripts/check-migrations.php`

## Violations

### 1. Hardcoded migrations namespace (line 27)

```php
migrations_namespace: 'ZeroToProd\\Thryds\\Migrations\\',
```

### 2. Hardcoded migrations directory (line 26)

```php
migrations_dir: __DIR__ . '/../migrations',
```

## Fix

1. Load `migrations-config.yaml` (shared with `generate-migration.php`, `migrate.php`, `migrate-status.php`, `migrate-rollback.php`):

```php
$config = Yaml::parseFile($base_dir . '/migrations-config.yaml');
$Migrator = new Migrator(
    Database: new Database(DatabaseConfig::fromEnv()),
    migrations_dir: $base_dir . '/' . $config['directory'],
    migrations_namespace: $config['namespace'] . '\\',
);
```

2. Add `use Symfony\Component\Yaml\Yaml;` import.

## Constraints

- Five migration scripts share the same namespace/directory — all must load from the same config.
- Do not change the Migrator constructor contract.
- Run `./run check:all` to verify no regressions.
