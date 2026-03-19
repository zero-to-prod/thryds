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
$existing = glob($base_dir . '/migrations/[0-9][0-9][0-9][0-9]_*.php') ?: [];
$max_id   = 0;
foreach ($existing as $file) {
    if (preg_match('/^(\d{4})_/', basename($file), $m)) {
        $max_id = max($max_id, (int) $m[1]);
    }
}
$next_id  = str_pad((string) ($max_id + 1), 4, '0', STR_PAD_LEFT);
$filename = "migrations/{$next_id}_{$class_name}.php";
$path     = $base_dir . '/' . $filename;

if (file_exists($path)) {
    echo "Error: File already exists: $filename\n";
    exit(1);
}

$description = preg_replace('/(?<!^)[A-Z]/', ' $0', $class_name) ?? $class_name;

$content = <<<PHP
<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\MigrationInterface;

#[Migration(id: '{$next_id}', description: '{$description}')]
final class {$class_name} implements MigrationInterface
{
    public function up(Database \$Database): void
    {
        // TODO: implement migration
    }

    public function down(Database \$Database): void
    {
        // TODO: implement rollback
    }
}
PHP;

file_put_contents(filename: $path, data: $content);

echo "  Created $filename\n";
echo "  Next step: implement up() and down(), then run: ./run migrate\n";
exit(0);
