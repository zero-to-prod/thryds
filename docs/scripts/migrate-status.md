# Fix: migrate-status.php — Externalize Hardcoded Namespace and Path

## Script

`scripts/migrate-status.php`

## Violations

### 1. Hardcoded migrations namespace (line 30)

```php
migrations_namespace: 'ZeroToProd\\Thryds\\Migrations\\',
```

### 2. Hardcoded migrations directory (line 29)

```php
migrations_dir: __DIR__ . '/../migrations',
```

## Fix

Load `migrations-config.yaml` (shared with `check-migrations.php`, `generate-migration.php`, `migrate.php`, `migrate-rollback.php`):

```php
$config = Yaml::parseFile(dirname(__DIR__) . '/migrations-config.yaml');
$Migrator = new Migrator(
    Database: new Database(DatabaseConfig::fromEnv()),
    migrations_dir: dirname(__DIR__) . '/' . $config['directory'],
    migrations_namespace: $config['namespace'] . '\\',
);
```

## Constraints

- Do not change the Migrator constructor contract.
- Run `./run check:all` to verify no regressions.
