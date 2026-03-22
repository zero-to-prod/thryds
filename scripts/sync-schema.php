<?php

declare(strict_types=1);

/**
 * Sync Column attribute values in Table model files to match the live MySQL schema.
 * Creates the table if it does not exist.
 *
 * Usage: docker compose exec web php scripts/sync-schema.php [--dry-run]
 * Via Composer: ./run sync:schema [-- --dry-run]
 *
 * --dry-run  Report drift and pending creates without modifying anything.
 *
 * Progress is written to stderr; JSON summary to stdout.
 * Exit 0 on success, 1 on error.
 */

require __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;
use ZeroToProd\Thryds\Attributes\Column;
use ZeroToProd\Thryds\Attributes\Table;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;
use ZeroToProd\Thryds\Schema\DataType;
use ZeroToProd\Thryds\Schema\DdlBuilder;

$tables_config  = Yaml::parseFile(__DIR__ . '/tables-config.yaml');
$tables_dir     = $tables_config['directory'];
$tables_ns      = $tables_config['namespace'];
$data_type_enum = $tables_config['data_type_enum'];

$dry_run = in_array('--dry-run', $argv, true);

$data_type_map = [];
foreach ($tables_config['data_type_map'] as $mysql_type => $enum_case) {
    $data_type_map[$mysql_type] = constant($data_type_enum . '::' . $enum_case);
}

try {
    $database = new Database(DatabaseConfig::fromEnv());
} catch (Throwable $e) {
    fwrite(STDERR, 'Error connecting to database: ' . $e->getMessage() . "\n");
    exit(1);
}

$result = [
    'created'                    => [],
    'synced'                     => [],
    'flagged_missing_from_model' => [],
    'flagged_missing_from_db'    => [],
    'no_changes'                 => [],
];

foreach (glob(__DIR__ . '/../' . $tables_dir . '/*.php') as $path) {
    $basename = basename($path, '.php');
    $fqcn     = $tables_ns . '\\' . $basename;

    try {
        $rc = new ReflectionClass($fqcn);
    } catch (ReflectionException $e) {
        fwrite(STDERR, "Warning: cannot reflect {$fqcn}: " . $e->getMessage() . "\n");

        continue;
    }

    $table_attrs = $rc->getAttributes(Table::class);

    if ($table_attrs === []) {
        continue;
    }

    $table_attr = $table_attrs[0]->newInstance();
    $table_name = $table_attr->TableName->value;

    fwrite(STDERR, "Processing: {$table_name} ({$basename}.php)\n");

    // Build $php_cols before querying DB so it is available for CREATE TABLE if needed.
    $php_cols = [];

    foreach ($rc->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
        $col_attrs = $prop->getAttributes(Column::class);

        if ($col_attrs === []) {
            continue;
        }

        $php_cols[$prop->getName()] = $col_attrs[0]->newInstance();
    }

    try {
        $db_rows = $database->all(
            'SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH,
                    NUMERIC_PRECISION, NUMERIC_SCALE, IS_NULLABLE, COLUMN_DEFAULT,
                    EXTRA, COLUMN_COMMENT
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
             ORDER BY ORDINAL_POSITION',
            [$table_name],
        );
    } catch (Throwable $e) {
        fwrite(STDERR, "  Error querying {$table_name}: " . $e->getMessage() . "\n");

        continue;
    }

    if ($db_rows === []) {
        $sql = DdlBuilder::createTableSql($fqcn);

        if ($dry_run) {
            fwrite(STDERR, "  [dry-run] Would create table {$table_name}.\n");
        } else {
            try {
                $database->execute($sql);
                fwrite(STDERR, "  Created table {$table_name}.\n");
            } catch (Throwable $e) {
                fwrite(STDERR, "  Error creating {$table_name}: " . $e->getMessage() . "\n");

                continue;
            }
        }

        $result['created'][] = ['table' => $table_name, 'class' => $fqcn, 'file' => "{$tables_dir}/{$basename}.php"];

        continue;
    }

    // Build $db_cols keyed by column name with normalised field values.
    $db_cols = [];

    foreach ($db_rows as $row) {
        $col_name  = $row['COLUMN_NAME'];
        $data_type = $data_type_map[$row['DATA_TYPE']] ?? null;

        if ($data_type === null) {
            fwrite(STDERR, "  Warning: unknown DATA_TYPE '{$row['DATA_TYPE']}' for column {$col_name}.\n");

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

        $db_cols[$col_name] = [
            'data_type'      => $data_type,
            'length'         => $row['CHARACTER_MAXIMUM_LENGTH'] !== null ? (int) $row['CHARACTER_MAXIMUM_LENGTH'] : null,
            'precision'      => $row['NUMERIC_PRECISION'] !== null ? (int) $row['NUMERIC_PRECISION'] : null,
            'scale'          => $row['NUMERIC_SCALE'] !== null ? (int) $row['NUMERIC_SCALE'] : null,
            'unsigned'       => str_contains(strtolower($row['COLUMN_TYPE']), 'unsigned'),
            'nullable'       => $row['IS_NULLABLE'] === 'YES',
            'auto_increment' => str_contains(strtolower((string) $row['EXTRA']), 'auto_increment'),
            'default'        => $default,
            'values'         => $values,
            'comment'        => (string) ($row['COLUMN_COMMENT'] ?? ''),
        ];
    }

    foreach (array_keys($db_cols) as $col_name) {
        if (! isset($php_cols[$col_name])) {
            $result['flagged_missing_from_model'][] = ['table' => $table_name, 'column' => $col_name];
        }
    }

    foreach (array_keys($php_cols) as $prop_name) {
        if (! isset($db_cols[$prop_name])) {
            $result['flagged_missing_from_db'][] = ['table' => $table_name, 'column' => $prop_name];
        }
    }

    // Compute per-property diffs against the live schema.
    $changes = [];

    foreach ($php_cols as $prop_name => $php_col) {
        if (! isset($db_cols[$prop_name])) {
            continue;
        }

        $db_col       = $db_cols[$prop_name];
        $db_data_type = $db_col['data_type'];

        // MySQL stores BOOLEAN as tinyint — preserve the PHP declaration if already BOOLEAN.
        if ($db_data_type === DataType::TINYINT && $php_col->DataType === DataType::BOOLEAN) {
            $db_data_type = DataType::BOOLEAN;
        }

        $diffs = [];

        if ($php_col->DataType !== $db_data_type) {
            $diffs[] = ['column' => $prop_name, 'field' => 'DataType', 'from' => $php_col->DataType->value, 'to' => $db_data_type->value];
        }

        if ($php_col->length !== $db_col['length']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'length', 'from' => $php_col->length, 'to' => $db_col['length']];
        }

        if ($php_col->precision !== $db_col['precision']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'precision', 'from' => $php_col->precision, 'to' => $db_col['precision']];
        }

        if ($php_col->scale !== $db_col['scale']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'scale', 'from' => $php_col->scale, 'to' => $db_col['scale']];
        }

        if ($php_col->unsigned !== $db_col['unsigned']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'unsigned', 'from' => $php_col->unsigned, 'to' => $db_col['unsigned']];
        }

        if ($php_col->nullable !== $db_col['nullable']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'nullable', 'from' => $php_col->nullable, 'to' => $db_col['nullable']];
        }

        if ($php_col->auto_increment !== $db_col['auto_increment']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'auto_increment', 'from' => $php_col->auto_increment, 'to' => $db_col['auto_increment']];
        }

        if ($php_col->default !== $db_col['default']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'default', 'from' => $php_col->default, 'to' => $db_col['default']];
        }

        if ($php_col->values !== $db_col['values']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'values', 'from' => $php_col->values, 'to' => $db_col['values']];
        }

        if ($php_col->comment !== $db_col['comment']) {
            $diffs[] = ['column' => $prop_name, 'field' => 'comment', 'from' => $php_col->comment, 'to' => $db_col['comment']];
        }

        if ($diffs !== []) {
            $changes[$prop_name] = ['diffs' => $diffs, 'db_col' => $db_col, 'db_data_type' => $db_data_type];
        }
    }

    $all_diffs = $changes !== [] ? array_merge(...array_column($changes, 'diffs')) : [];

    if ($dry_run) {
        if ($changes !== []) {
            fwrite(STDERR, '  [dry-run] Would update ' . count($changes) . " column(s).\n");
            $result['synced'][] = [
                'table'   => $table_name,
                'class'   => $fqcn,
                'file'    => "{$tables_dir}/{$basename}.php",
                'changes' => $all_diffs,
            ];
        } else {
            $result['no_changes'][] = $table_name;
            fwrite(STDERR, "  No changes.\n");
        }

        continue;
    }

    // Re-render ALL matching columns for canonical parameter order, applying DB values where they differ.
    $lines            = file($path, FILE_IGNORE_NEW_LINES);
    $original_content = implode("\n", $lines) . "\n";

    foreach ($php_cols as $prop_name => $php_col) {
        if (! isset($db_cols[$prop_name])) {
            continue;
        }

        $db_col       = $db_cols[$prop_name];
        $db_data_type = $db_col['data_type'];

        if ($db_data_type === DataType::TINYINT && $php_col->DataType === DataType::BOOLEAN) {
            $db_data_type = DataType::BOOLEAN;
        }

        $new_col = new Column(
            DataType: $db_data_type,
            length: $db_col['length'],
            precision: $db_col['precision'],
            scale: $db_col['scale'],
            unsigned: $db_col['unsigned'],
            nullable: $db_col['nullable'],
            auto_increment: $db_col['auto_increment'],
            default: $db_col['default'],
            values: $db_col['values'],
            comment: $db_col['comment'],
        );

        $rendered = render_column_attribute($new_col);
        $found    = false;

        for ($i = 0; $i < count($lines); $i++) {
            if (trim($lines[$i]) !== '#[Column(') {
                continue;
            }

            // Locate the matching closing )] line.
            $end = $i;

            for ($j = $i + 1; $j < count($lines); $j++) {
                if (trim($lines[$j]) === ')]') {
                    $end = $j;

                    break;
                }
            }

            // Walk forward past sibling attributes to find the property declaration.
            $prop_line = null;

            for ($k = $end + 1; $k < count($lines); $k++) {
                $trimmed = trim($lines[$k]);

                if ($trimmed === '' || str_starts_with($trimmed, '#[')) {
                    continue;
                }

                if (preg_match('/^public \??\w[\w|]*\s+\$(\w+)/', $trimmed, $m) && $m[1] === $prop_name) {
                    $prop_line = $k;
                }

                break;
            }

            if ($prop_line === null) {
                continue;
            }

            // Replace the #[Column(...)] block with the re-rendered lines.
            $indent    = strlen($lines[$i]) - strlen(ltrim($lines[$i]));
            $pad       = str_repeat(' ', $indent);
            $new_lines = array_map(static fn($l) => $pad . $l, $rendered);
            $old_count = $end - $i + 1;

            array_splice($lines, $i, $old_count, $new_lines);

            $prop_line += count($new_lines) - $old_count;

            // Update the nullable type hint if nullability changed.
            if ($php_col->nullable !== $db_col['nullable']) {
                if ($db_col['nullable']) {
                    $lines[$prop_line] = preg_replace(
                        '/(\bpublic\s+)(\w+)(\s+\$' . preg_quote($prop_name, '/') . ';)/',
                        '$1?$2$3',
                        $lines[$prop_line],
                    );
                } else {
                    $lines[$prop_line] = preg_replace(
                        '/(\bpublic\s+)\?(\w+)(\s+\$' . preg_quote($prop_name, '/') . ';)/',
                        '$1$2$3',
                        $lines[$prop_line],
                    );
                }
            }

            $found = true;

            break;
        }

        if (! $found) {
            fwrite(STDERR, "  Warning: could not locate #[Column] block for \${$prop_name}.\n");
        }
    }

    $new_content = implode("\n", $lines) . "\n";

    if ($new_content !== $original_content) {
        file_put_contents($path, $new_content);
        fwrite(STDERR, "  Updated {$basename}.php\n");
        $result['synced'][] = [
            'table'   => $table_name,
            'class'   => $fqcn,
            'file'    => "{$tables_dir}/{$basename}.php",
            'changes' => $all_diffs,
        ];
    } else {
        $result['no_changes'][] = $table_name;
        fwrite(STDERR, "  No changes.\n");
    }
}

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";

exit(0);

/**
 * Renders a #[Column(...)] attribute block as an array of unindented lines.
 * Parameters are always written in constructor-declaration order.
 *
 * @return string[]
 */
function render_column_attribute(Column $col): array
{
    return [
        '#[Column(',
        '    DataType: DataType::' . $col->DataType->name . ',',
        '    length: ' . render_scalar($col->length) . ',',
        '    precision: ' . render_scalar($col->precision) . ',',
        '    scale: ' . render_scalar($col->scale) . ',',
        '    unsigned: ' . render_scalar($col->unsigned) . ',',
        '    nullable: ' . render_scalar($col->nullable) . ',',
        '    auto_increment: ' . render_scalar($col->auto_increment) . ',',
        '    default: ' . render_default($col->default) . ',',
        '    values: ' . render_values($col->values) . ',',
        '    comment: ' . render_string($col->comment) . ',',
        ')]',
    ];
}

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
