# Fix: generate-table.php — Externalize Hardcoded Data Type Map, Namespace, and Template

## Script

`scripts/generate-table.php`

## Violations

### 1. Hardcoded data type map (lines 34-60)

Duplicate of `sync-schema.php` lines 30-56.

### 2. Hardcoded namespace (line 231)

```php
namespace ZeroToProd\Thryds\Tables;
```

### 3. Hardcoded output path (line 121)

```php
$out_path = $base_dir . '/src/Tables/' . $class_name . '.php';
```

### 4. Hardcoded TableName enum path (line 128)

```php
$table_name_path = $base_dir . '/src/Tables/TableName.php';
```

### 5. Hardcoded import references (lines 232-242)

All `use ZeroToProd\Thryds\*` imports in the generated template are project-specific.

## Fix

1. Load `tables-config.yaml` (shared with `sync-schema.php`).

2. Extend the config with template generation values:

```yaml
directory: src/Tables
namespace: ZeroToProd\Thryds\Tables
table_name_enum: TableName
data_type_enum: ZeroToProd\Thryds\Schema\DataType
imports:
  - ZeroToProd\Thryds\Attributes\ClosedSet
  - ZeroToProd\Thryds\Attributes\Column
  - ZeroToProd\Thryds\Attributes\DataModel
  - ZeroToProd\Thryds\Attributes\HasTableName
  - ZeroToProd\Thryds\Attributes\Table
  - ZeroToProd\Thryds\Schema\Charset
  - ZeroToProd\Thryds\Schema\Collation
  - ZeroToProd\Thryds\Schema\DataType
  - ZeroToProd\Thryds\Schema\Engine
  - ZeroToProd\Thryds\UI\Domain
```

3. Build the data type map, paths, namespace, and template imports from config values.

4. The `render_scalar()`, `render_default()`, `render_values()`, `render_string()` helpers are duplicated with `sync-schema.php` — extract to a shared include if desired.

## Constraints

- The generated Table class must have the same structure as today.
- The `$data_type_map`, `$int_types`, and `$float_types` arrays all derive from the DataType enum.
- Run `./run check:all` to verify no regressions.
