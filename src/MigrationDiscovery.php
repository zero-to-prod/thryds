<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Attributes\Migration as MigrationAttribute;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * Discovers migration classes from the filesystem via their migration attribute.
 *
 * Scans all PHP files in a directory, derives the class name by stripping any
 * optional numeric prefix (e.g. 0001_), and uses the migration attribute as
 * the sole source of truth for id, description, and ordering. Filenames are not
 * authoritative — the attribute drives discovery.
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
     * Discovers migration classes, sorted by attribute id.
     *
     * @return array<string, array<string, string>>
     */
    private static function discover(string $migrations_dir, string $migrations_namespace): array
    {
        $files = glob($migrations_dir . '/*.php');
        if ($files === false || $files === []) {
            return [];
        }
        sort(array: $files);
        /** @var array<string, array<string, string>> $migrations */
        $migrations = [];
        foreach ($files as $path) {
            $class_name = preg_replace('/^\d+_/', '', basename($path, suffix: '.php'));
            if ($class_name === '' || $class_name === null) {
                continue;
            }
            $fqcn = $migrations_namespace . $class_name;
            if (!class_exists(class: $fqcn)) {
                continue;
            }
            $attrs = new ReflectionClass(objectOrClass: $fqcn)->getAttributes(MigrationAttribute::class);
            if ($attrs === []) {
                continue;
            }
            $Migration = $attrs[0]->newInstance();
            $migrations[$Migration->id] = [
                self::key_path         => $path,
                self::key_class        => $fqcn,
                Migration::description => $Migration->description,
            ];
        }
        ksort(array: $migrations);

        return $migrations;
    }
}
