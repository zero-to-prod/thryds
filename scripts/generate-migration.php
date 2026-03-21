<?php

declare(strict_types=1);

/**
 * Scaffolds a new migration file.
 *
 * Usage: docker compose exec web php scripts/generate-migration.php <ClassName>
 * Via Composer: ./run generate:migration -- <ClassName>
 *
 * Example: ./run generate:migration -- CreateUsersTable
 *
 * Generates: migrations/NNNN_<ClassName>.php
 *
 * The next id is auto-determined from the highest existing file prefix.
 * Class must be PascalCase and should describe what the migration does
 * (e.g. CreateUsersTable, AddEmailIndexToUsers, DropLegacyTokensTable).
 */

$base_dir = dirname(__DIR__);

require $base_dir . '/vendor/autoload.php';

use Symfony\Component\Yaml\Yaml;

$config = Yaml::parseFile(__DIR__ . '/migrations-config.yaml');

$args = array_values(array_filter(array_slice($argv, 1), static fn(string $a): bool => !str_starts_with($a, '--')));

if ($args === []) {
    echo "Usage: php scripts/generate-migration.php <ClassName>\n";
    echo "Example: ./run generate:migration -- CreateUsersTable\n";
    exit(1);
}

$class_name = $args[0];

if (!preg_match('/^[A-Z][a-zA-Z0-9]+$/', $class_name)) {
    echo "Error: Class name must be PascalCase (e.g. CreateUsersTable).\n";
    exit(1);
}

// Auto-determine next id
$migrations_dir = $config['directory'];
$existing = glob($base_dir . '/' . $migrations_dir . '/[0-9][0-9][0-9][0-9]_*.php') ?: [];
$max_id   = 0;
foreach ($existing as $file) {
    if (preg_match('/^(\d{4})_/', basename($file), $m)) {
        $max_id = max($max_id, (int) $m[1]);
    }
}
$next_id  = str_pad((string) ($max_id + 1), 4, '0', STR_PAD_LEFT);
$filename = "{$migrations_dir}/{$next_id}_{$class_name}.php";
$path     = $base_dir . '/' . $filename;

if (file_exists($path)) {
    echo "Error: File already exists: $filename\n";
    exit(1);
}

$description = preg_replace('/(?<!^)[A-Z]/', ' $0', $class_name) ?? $class_name;

$namespace  = $config['namespace'];
$imports    = implode("\n", array_map(static fn(string $fqcn): string => "use {$fqcn};", $config['imports']));
$interface  = $config['interface'];
$attribute  = $config['attribute'];
$db_class   = new ReflectionClass($config['imports'][1])->getShortName();

$content = <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

{$imports}

#[{$attribute}(id: '{$next_id}', description: '{$description}')]
final readonly class {$class_name} implements {$interface}
{
    public function up({$db_class} \$Database): void
    {
        // DDL example: CREATE TABLE IF NOT EXISTS `table` (...) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        // DML example: \$Database->execute('INSERT INTO `table` (col) VALUES (:col)', [':col' => 'value'])
    }

    public function down({$db_class} \$Database): void
    {
        // Undo up() defensively — DDL auto-commits, so partial states are possible.
        // DDL example: DROP TABLE IF EXISTS `table`
        // DML example: \$Database->execute('DELETE FROM `table` WHERE col = :col', [':col' => 'value'])
    }
}
PHP;

file_put_contents(filename: $path, data: $content);

echo json_encode(
    value: [
        'created'    => [$filename],
        'next_steps' => [
            ['action' => "Implement up() and down() in {$filename}"],
            ['action' => 'Apply the migration', 'command' => './run migrate'],
        ],
    ],
    flags: JSON_PRETTY_PRINT,
) . "\n";
exit(0);
