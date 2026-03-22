<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use RuntimeException;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Migration as MigrationAttribute;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * Discovers migration files from the filesystem.
 *
 * Scans a directory for files matching NNNN_ClassName.php, validates each
 * against its #[Migration] attribute, and provides typed accessors for the
 * discovered set. Checksum computation lives here because it is a property
 * of the discovered file, not of the database state.
 */
#[Infrastructure]
readonly class MigrationDiscovery
{
    private const string key_path = 'path';

    private const string key_class = 'class';

    /** @var array<string, array<string, string>> */
    private array $discovered;

    public function __construct(
        private string $migrations_dir,
        private string $migrations_namespace,
    ) {
        $this->discovered = self::discover($this->migrations_dir, $this->migrations_namespace);
    }

    /** @return list<string> */
    public function ids(): array
    {
        return array_keys($this->discovered);
    }

    public function has(string $id): bool
    {
        return isset($this->discovered[$id]);
    }

    public function path(string $id): string
    {
        return $this->discovered[$id][self::key_path];
    }

    public function fqcn(string $id): string
    {
        return $this->discovered[$id][self::key_class];
    }

    public function description(string $id): string
    {
        return $this->discovered[$id][Migration::description];
    }

    public function checksum(string $id): string
    {
        return hash(algo: 'sha256', data: (string) file_get_contents(filename: $this->path($id)));
    }

    public function isEmpty(): bool
    {
        return $this->discovered === [];
    }

    /**
     * Discovers migration files, sorted by id.
     *
     * @return array<string, array<string, string>>
     */
    private static function discover(string $migrations_dir, string $migrations_namespace): array
    {
        $files = glob($migrations_dir . '/[0-9][0-9][0-9][0-9]_*.php');
        if ($files === false || $files === []) {
            return [];
        }
        sort(array: $files);
        /** @var array<string, array<string, string>> $migrations */
        $migrations = [];
        foreach ($files as $path) {
            if (!preg_match('/^(\d{4})_(.+)$/', basename($path, suffix: '.php'), $matches)) {
                continue;
            }
            $fqcn = $migrations_namespace . $matches[2];
            if (!class_exists(class: $fqcn)) {
                continue;
            }
            $attrs = new ReflectionClass(objectOrClass: $fqcn)->getAttributes(MigrationAttribute::class);
            if ($attrs === []) {
                continue;
            }
            $Migration = $attrs[0]->newInstance();
            if ($Migration->id !== $matches[1]) {
                throw new RuntimeException(
                    "Migration attribute id '{$Migration->id}' does not match filename prefix '{$matches[1]}' in " . basename($path) . ' — keep the attribute id and filename prefix in sync.'
                );
            }
            $migrations[$Migration->id] = [
                self::key_path               => $path,
                self::key_class              => $fqcn,
                Migration::description => $Migration->description,
            ];
        }
        ksort(array: $migrations);

        return $migrations; // @phpstan-ignore return.type
    }
}
