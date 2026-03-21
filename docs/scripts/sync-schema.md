# Fix: sync-schema.php — Externalize Hardcoded Data Type Map, Namespace, and Path

## Script

`scripts/sync-schema.php`

## Violations

### 1. Hardcoded data type map (lines 30-56)

```php
$data_type_map = [
    'bigint'     => DataType::BIGINT,
    'binary'     => DataType::BINARY,
    ...24 entries...
];
```

This map is duplicated identically in `generate-table.php` (lines 34-60).

### 2. Hardcoded table discovery path (line 73)

```php
foreach (glob(__DIR__ . '/../src/Tables/*.php') as $path) {
```

### 3. Hardcoded table namespace (line 75)

```php
$fqcn = 'ZeroToProd\\Thryds\\Tables\\' . $basename;
```

## Fix

1. Create `tables-config.yaml` at the project root:

```yaml
directory: src/Tables
namespace: ZeroToProd\Thryds\Tables
data_type_enum: ZeroToProd\Thryds\Schema\DataType
data_type_map:
  bigint: BIGINT
  binary: BINARY
  blob: BLOB
  char: CHAR
  date: DATE
  datetime: DATETIME
  decimal: DECIMAL
  double: DOUBLE
  enum: ENUM
  float: FLOAT
  int: INT
  json: JSON
  longblob: LONGBLOB
  longtext: LONGTEXT
  mediumblob: MEDIUMBLOB
  mediumtext: MEDIUMTEXT
  set: SET
  smallint: SMALLINT
  text: TEXT
  time: TIME
  timestamp: TIMESTAMP
  tinyint: TINYINT
  varbinary: VARBINARY
  varchar: VARCHAR
  year: YEAR
```

2. In the script, load the config and build the data type map dynamically:

```php
$config = Yaml::parseFile(__DIR__ . '/../tables-config.yaml');
$dataTypeEnum = $config['data_type_enum'];
$data_type_map = [];
foreach ($config['data_type_map'] as $mysql_type => $enum_case) {
    $data_type_map[$mysql_type] = constant($dataTypeEnum . '::' . $enum_case);
}
$tables_dir = __DIR__ . '/../' . $config['directory'];
$tables_namespace = $config['namespace'] . '\\';
```

3. Share `tables-config.yaml` with `generate-table.php`.

## Constraints

- The `render_column_attribute()`, `build_create_table_sql()`, and DDL helper functions are generic — keep them as-is.
- The data type map values must resolve to valid `DataType` enum cases.
- Run `./run check:all` to verify no regressions.
