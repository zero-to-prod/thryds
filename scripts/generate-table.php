<?php

declare(strict_types=1);

/**
 * Generate a Table model class from a live database table's schema.
 *
 * Usage: docker compose exec web php scripts/generate-table.php <table_name> [--force]
 * Via Composer: ./run generate:table -- <table_name> [--force]
 *
 * --force  Overwrite an existing Table model file.
 *
 * Progress is written to stderr; JSON summary to stdout.
 * Exit 0 on success, 1 on error.
 */

require __DIR__ . '/../vendor/autoload.php';

use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Schema\DataType;

$args       = array_values(array_filter(array_slice($argv, 1), static fn(string $a): bool => ! str_starts_with($a, '--')));
$force      = in_array('--force', $argv, true);
$table_name = $args[0] ?? '';

if ($table_name === '') {
    fwrite(STDERR, "Usage: php scripts/generate-table.php <table_name> [--force]\n");
    fwrite(STDERR, "Example: ./run generate:table -- users\n");
    exit(1);
}

$data_type_map = [
    'bigint'     => DataType::BIGINT,
    'binary'     => DataType::BINARY,
    'blob'       => DataType::BLOB,
    'char'       => DataType::CHAR,
    'date'       => DataType::DATE,
    'datetime'   => DataType::DATETIME,
    'decimal'    => DataType::DECIMAL,
    'double'     => DataType::DOUBLE,
    'enum'       => DataType::ENUM,
    'float'      => DataType::FLOAT,
    'int'        => DataType::INT,
    'json'       => DataType::JSON,
    'longblob'   => DataType::LONGBLOB,
    'longtext'   => DataType::LONGTEXT,
    'mediumblob' => DataType::MEDIUMBLOB,
    'mediumtext' => DataType::MEDIUMTEXT,
    'set'        => DataType::SET,
    'smallint'   => DataType::SMALLINT,
    'text'       => DataType::TEXT,
    'time'       => DataType::TIME,
    'timestamp'  => DataType::TIMESTAMP,
    'tinyint'    => DataType::TINYINT,
    'varbinary'  => DataType::VARBINARY,
    'varchar'    => DataType::VARCHAR,
    'year'       => DataType::YEAR,
];

$int_types   = [DataType::INT, DataType::BIGINT, DataType::SMALLINT, DataType::TINYINT];
$float_types = [DataType::FLOAT, DataType::DOUBLE, DataType::DECIMAL];

try {
    $db = new Database(DatabaseConfig::fromEnv());
} catch (Throwable $e) {
    fwrite(STDERR, 'Error connecting to database: ' . $e->getMessage() . "\n");
    exit(1);
}

$table_rows = $db->all(
    'SELECT ENGINE, TABLE_COLLATION
     FROM INFORMATION_SCHEMA.TABLES
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
    [$table_name],
);

if ($table_rows === []) {
    fwrite(STDERR, "Error: table '{$table_name}' not found in the database.\n");
    exit(1);
}

$engine_val    = $table_rows[0]['ENGINE'] ?? 'InnoDB';
$collation_val = $table_rows[0]['TABLE_COLLATION'] ?? 'utf8mb4_unicode_ci';
$charset_val   = explode('_', $collation_val)[0];

$col_rows = $db->all(
    'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH,
            NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE, COLUMN_DEFAULT,
            EXTRA, COLUMN_COMMENT
     FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
     ORDER BY ORDINAL_POSITION',
    [$table_name],
);

if ($col_rows === []) {
    fwrite(STDERR, "Error: no columns found for table '{$table_name}'.\n");
    exit(1);
}

$pk_rows    = $db->all(
    'SELECT k.COLUMN_NAME
     FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS c
     JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE k
       ON k.TABLE_SCHEMA    = c.TABLE_SCHEMA
      AND k.TABLE_NAME      = c.TABLE_NAME
      AND k.CONSTRAINT_NAME = c.CONSTRAINT_NAME
     WHERE c.TABLE_SCHEMA    = DATABASE()
       AND c.TABLE_NAME      = ?
       AND c.CONSTRAINT_TYPE = \'PRIMARY KEY\'
     ORDER BY k.ORDINAL_POSITION',
    [$table_name],
);
$pk_columns = array_column($pk_rows, 'COLUMN_NAME');

// snake_case → PascalCase
$class_name = implode('', array_map('ucfirst', explode('_', $table_name)));
$base_dir   = dirname(__DIR__);
$out_path   = $base_dir . '/src/Tables/' . $class_name . '.php';

if (file_exists($out_path) && ! $force) {
    fwrite(STDERR, "Error: src/Tables/{$class_name}.php already exists. Use --force to overwrite.\n");
    exit(1);
}

$table_name_path    = $base_dir . '/src/Tables/TableName.php';
$table_name_content = file_get_contents($table_name_path);

if (! str_contains($table_name_content, "case {$table_name} =")) {
    $table_name_content = str_replace(
        "\n}",
        "\n    case {$table_name} = '{$table_name}';\n}",
        $table_name_content,
    );
    file_put_contents($table_name_path, $table_name_content);
    fwrite(STDERR, "Added TableName::{$table_name} to src/Tables/TableName.php\n");
}

$prop_blocks     = [];
$needs_pk_import = $pk_columns !== [];

foreach ($col_rows as $row) {
    $col_name  = $row['COLUMN_NAME'];
    $data_type = $data_type_map[strtolower($row['DATA_TYPE'])] ?? null;

    if ($data_type === null) {
        fwrite(STDERR, "Warning: unknown DATA_TYPE '{$row['DATA_TYPE']}' for column '{$col_name}', skipping.\n");

        continue;
    }

    $values = null;

    if ($data_type === DataType::ENUM || $data_type === DataType::SET) {
        preg_match_all("/'([^']*)'/", $row['COLUMN_TYPE'], $m);
        $values = $m[1];
    }

    $default = null;

    if ($row['COLUMN_DEFAULT'] !== null) {
        $lower = strtolower((string) $row['COLUMN_DEFAULT']);

        if ($lower === 'current_timestamp' || $lower === 'current_timestamp()') {
            $default = Column::CURRENT_TIMESTAMP;
        } elseif (is_numeric($row['COLUMN_DEFAULT'])) {
            $default = $row['COLUMN_DEFAULT'] + 0;
        } else {
            $default = $row['COLUMN_DEFAULT'];
        }
    }

    $nullable       = $row['IS_NULLABLE'] === 'YES';
    $auto_increment = str_contains(strtolower((string) $row['EXTRA']), 'auto_increment');
    $unsigned       = str_contains(strtolower($row['COLUMN_TYPE']), 'unsigned');
    $length         = $row['CHARACTER_MAXIMUM_LENGTH'] !== null ? (int) $row['CHARACTER_MAXIMUM_LENGTH'] : null;
    $precision      = $row['NUMERIC_PRECISION'] !== null ? (int) $row['NUMERIC_PRECISION'] : null;
    $scale          = $row['NUMERIC_SCALE'] !== null ? (int) $row['NUMERIC_SCALE'] : null;
    $comment        = (string) ($row['COLUMN_COMMENT'] ?? '');
    $is_pk          = in_array($col_name, $pk_columns, true);

    $php_type = 'string';

    if (in_array($data_type, $int_types, true)) {
        $php_type = 'int';
    } elseif (in_array($data_type, $float_types, true)) {
        $php_type = 'float';
    } elseif ($data_type === DataType::BOOLEAN) {
        $php_type = 'bool';
    }

    $type_hint = ($nullable ? '?' : '') . $php_type;

    $col_block  = "    /** @see \${$col_name} */\n";
    $col_block .= "    public const string {$col_name} = '{$col_name}';\n";
    $col_block .= "    #[Column(\n";
    $col_block .= "        DataType: DataType::{$data_type->name},\n";
    $col_block .= '        length: ' . render_scalar($length) . ",\n";
    $col_block .= '        precision: ' . render_scalar($precision) . ",\n";
    $col_block .= '        scale: ' . render_scalar($scale) . ",\n";
    $col_block .= '        unsigned: ' . render_scalar($unsigned) . ",\n";
    $col_block .= '        nullable: ' . render_scalar($nullable) . ",\n";
    $col_block .= '        auto_increment: ' . render_scalar($auto_increment) . ",\n";
    $col_block .= '        default: ' . render_default($default) . ",\n";
    $col_block .= '        values: ' . render_values($values) . ",\n";
    $col_block .= '        comment: ' . render_string($comment) . ",\n";
    $col_block .= "    )]\n";

    if ($is_pk) {
        $col_block .= "    #[PrimaryKey(columns: [])]\n";
    }

    $col_block .= "    public {$type_hint} \${$col_name};";

    $prop_blocks[] = $col_block;
}

$props_str  = implode("\n\n", $prop_blocks);
$pk_use     = $needs_pk_import ? "\nuse ZeroToProd\\Thryds\\Attributes\\PrimaryKey;" : '';
$engine     = $engine_val;
$charset    = $charset_val;
$collation  = $collation_val;

$content = <<<PHP
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tables;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\DataModel;
use ZeroToProd\Thryds\Attributes\HasTableName;{$pk_use}
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Schema\Charset;
use ZeroToProd\Thryds\Schema\Collation;
use ZeroToProd\Thryds\Schema\DataType;
use ZeroToProd\Thryds\Schema\Engine;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::database_table_columns,
    addCase: <<<TEXT
    1. Add enum case with #[Column] attribute.
    2. Write a migration to ALTER TABLE {$table_name} ADD COLUMN ...
    TEXT
)]
#[Table(
    TableName: TableName::{$table_name},
    Engine: Engine::{$engine},
    Charset: Charset::{$charset},
    Collation: Collation::{$collation}
)]
/**
 * Schema definition for the {$table_name} table.
 *
 * Use the constant values as column name references in queries:
 * e.g. {$class_name}::id === 'id'
 */
class {$class_name}
{
    use DataModel;
    use HasTableName;

{$props_str}
}
PHP;

file_put_contents($out_path, $content . "\n");
fwrite(STDERR, "Generated src/Tables/{$class_name}.php\n");

echo json_encode(
    [
        'created'    => ["src/Tables/{$class_name}.php", 'src/Tables/TableName.php'],
        'table'      => $table_name,
        'class'      => "ZeroToProd\\Thryds\\Tables\\{$class_name}",
        'next_steps' => [
            ['action' => "Review the generated src/Tables/{$class_name}.php"],
            ['action' => 'Verify column attributes match the live schema', 'command' => './run sync:schema -- --dry-run'],
        ],
    ],
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
) . "\n";

exit(0);

function render_scalar(mixed $v): string
{
    if ($v === null) {
        return 'null';
    }

    if ($v === true) {
        return 'true';
    }

    if ($v === false) {
        return 'false';
    }

    if (is_int($v) || is_float($v)) {
        return (string) $v;
    }

    return render_string((string) $v);
}

function render_default(mixed $v): string
{
    if ($v === null) {
        return 'null';
    }

    if ($v === Column::CURRENT_TIMESTAMP) {
        return 'Column::CURRENT_TIMESTAMP';
    }

    return render_scalar($v);
}

function render_values(?array $v): string
{
    if ($v === null) {
        return 'null';
    }

    $items = array_map('render_string', $v);

    return '[' . implode(', ', $items) . ']';
}

function render_string(string $s): string
{
    return "'" . str_replace("'", "\\'", $s) . "'";
}
