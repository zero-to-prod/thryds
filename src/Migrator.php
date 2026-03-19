<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use RuntimeException;
use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;
use ZeroToProd\Thryds\Attributes\Migration;

// TODO: [SuggestEnumForInternalOnlyConstantsRector] Migrator has 8 string constants only referenced internally — consider migrating to a backed enum. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md
/**
 * Applies and rolls back database migrations.
 *
 * Migrations live in the directory passed to the constructor, named
 * NNNN_ClassName.php (e.g. 0001_CreateUsersTable.php). Each file must:
 *   - Declare a class in the ZeroToProd\Thryds\Migrations namespace
 *   - Implement MigrationInterface
 *   - Carry a #[Migration(id: 'NNNN', description: '...')] attribute
 *
 * State is tracked in a `migrations` table. Checksums detect files that were
 * edited after being applied — migrate() throws on any mismatch.
 *
 * DDL note: ensureTable() and migration up()/down() methods that run DDL
 * (CREATE TABLE, ALTER TABLE, etc.) cause MySQL to implicitly commit any open
 * transaction. Call ensureTable() before opening a transaction.
 */
#[KeyRegistry(
    KeySource::migrations_table,
    addKey: '1. Add constant. 2. Reference via Migrator::CONST_NAME where needed.',
)]
readonly class Migrator
{
    // --- Public API keys (keys in the arrays returned by status()) ---

    public const string col_id = 'id';

    public const string col_description = 'description';

    public const string col_checksum = 'checksum';

    public const string col_applied_at = 'applied_at';

    public const string col_status = 'status';

    // --- Public status values (possible values of col_status) ---

    public const string status_pending = 'pending';

    public const string status_applied = 'applied';

    public const string status_modified = 'modified';

    // --- Private implementation constants ---

    private const string table = 'migrations';

    private const string key_path = 'path';

    private const string key_class = 'class';

    private const string param_id = ':id';

    private const string param_description = ':description';

    private const string param_checksum = ':checksum';

    public function __construct(
        private Database $Database,
        private string $migrations_dir,
    ) {}

    public function ensureTable(): void
    {
        $this->Database->execute(
            'CREATE TABLE IF NOT EXISTS `' . self::table . '` (
                id          VARCHAR(20)  NOT NULL,
                description VARCHAR(255) NOT NULL,
                checksum    VARCHAR(64)  NOT NULL,
                applied_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    /**
     * Returns one row per migration file, ordered by id.
     *
     * Each row: {col_id, col_description, col_status, col_applied_at, col_checksum}
     * col_status is one of: status_applied, status_pending, status_modified.
     *
     * @return array<int, array<string, mixed>>
     */
    public function status(): array
    {
        $applied = [];
        foreach ($this->fetchApplied() as $row) {
            $applied[$this->rowStr($row, key: self::col_id)] = $row;
        }

        $result = [];
        foreach ($this->discover() as $id => $info) {
            $checksum = $this->checksum(path: $info[self::key_path]);
            if (isset($applied[$id])) {
                $result[] = [
                    self::col_id          => $id,
                    self::col_description => $info[self::col_description],
                    self::col_status      => $checksum === $applied[$id][self::col_checksum] ? self::status_applied : self::status_modified,
                    self::col_applied_at  => $applied[$id][self::col_applied_at],
                    self::col_checksum    => $checksum,
                ];
            } else {
                $result[] = [
                    self::col_id          => $id,
                    self::col_description => $info[self::col_description],
                    self::col_status      => self::status_pending,
                    self::col_applied_at  => null,
                    self::col_checksum    => $checksum,
                ];
            }
        }

        return $result;
    }

    /** Applies all pending migrations in id order. Throws if any applied migration has been modified. */
    public function migrate(): void
    {
        $discovered = $this->discover();
        foreach ($this->status() as $row) {
            $id = $this->rowStr($row, key: self::col_id);
            if ($row[self::col_status] === self::status_modified) {
                throw new RuntimeException(
                    "Migration $id was modified after being applied — checksum mismatch. Restore the file or roll back."
                );
            }
            if ($row[self::col_status] !== self::status_pending) {
                continue;
            }
            $this->instantiate(class: $discovered[$id][self::key_class])->up(Database: $this->Database);
            $this->Database->execute(
                'INSERT INTO `' . self::table . '` (id, description, checksum, applied_at) VALUES (' . self::param_id . ', ' . self::param_description . ', ' . self::param_checksum . ', NOW())',
                [self::param_id => $id, self::param_description => $row[self::col_description], self::param_checksum => $row[self::col_checksum]]
            );
            echo '  [ OK ] applied ' . $id . ' ' . $this->rowStr($row, key: self::col_description) . "\n";
        }
    }

    /** Rolls back the most recently applied migration. */
    public function rollback(): void
    {
        $last = $this->fetchLastApplied();
        if ($last === null) {
            echo "  Nothing to roll back.\n";

            return;
        }
        $id = $this->rowStr(row: $last, key: self::col_id);
        $discovered = $this->discover();
        if (!isset($discovered[$id])) {
            throw new RuntimeException("Migration $id is applied but its file was not found in {$this->migrations_dir}.");
        }
        $this->instantiate(class: $discovered[$id][self::key_class])->down(Database: $this->Database);
        $this->Database->execute(
            'DELETE FROM `' . self::table . '` WHERE id = ' . self::param_id,
            [self::param_id => $id]
        );
        echo '  [ OK ] rolled back ' . $id . ' ' . $this->rowStr(row: $last, key: self::col_description) . "\n";
    }

    /**
     * Discovers migration files, sorted by id.
     *
     * @return array<string, array<string, string>>
     */
    private function discover(): array
    {
        $files = glob($this->migrations_dir . '/[0-9][0-9][0-9][0-9]_*.php');
        if ($files === false || $files === []) {
            return [];
        }
        sort(array: $files);
        $migrations = [];
        foreach ($files as $path) {
            // TODO: [opcache] dynamic include prevents OPcache optimization. See: utils/rector/docs/ForbidDynamicIncludeRector.md
            require_once $path;
            if (!preg_match('/^(\d{4})_(.+)$/', basename($path, suffix: '.php'), $matches)) {
                continue;
            }
            $fqcn = 'ZeroToProd\\Thryds\\Migrations\\' . $matches[2];
            if (!class_exists(class: $fqcn)) {
                continue;
            }
            $attrs = new ReflectionClass(objectOrClass: $fqcn)->getAttributes(Migration::class);
            if ($attrs === []) {
                continue;
            }
            $Migration = $attrs[0]->newInstance();
            $migrations[$Migration->id] = [
                self::key_path        => $path,
                self::key_class       => $fqcn,
                self::col_description => $Migration->description,
            ];
        }
        ksort(array: $migrations);

        return $migrations;
    }

    /** @return array<int, array<string, mixed>> */
    private function fetchApplied(): array
    {
        return $this->Database->all($this->selectFromTable('ASC'));
    }

    /** @return array<string, mixed>|null */
    private function fetchLastApplied(): ?array
    {
        return $this->Database->one($this->selectFromTable('DESC LIMIT 1'));
    }

    private function selectFromTable(string $order): string
    {
        return 'SELECT * FROM `' . self::table . '` ORDER BY ' . self::col_id . ' ' . $order;
    }

    private function checksum(string $path): string
    {
        return hash(algo: 'sha256', data: (string) file_get_contents(filename: $path));
    }

    /**
     * Reads a string value from a database row, asserting the type.
     *
     * Database rows are typed as array<string, mixed>. This helper narrows
     * the value to string for PHPStan and throws on unexpected types at runtime.
     *
     * @param array<string, mixed> $row
     */
    private function rowStr(array $row, string $key): string
    {
        $value = $row[$key];
        assert(is_string($value));

        return $value;
    }

    private function instantiate(string $class): MigrationInterface
    {
        assert(class_exists($class));
        $instance = new ReflectionClass(objectOrClass: $class)->newInstance();
        if (!$instance instanceof MigrationInterface) {
            throw new RuntimeException("$class must implement MigrationInterface.");
        }

        return $instance;
    }
}
