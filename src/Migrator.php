<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use RuntimeException;
use ZeroToProd\Thryds\Attributes\CreateTable;
use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;
use ZeroToProd\Thryds\Attributes\Migration as MigrationAttribute;
use ZeroToProd\Thryds\Schema\DdlBuilder;
use ZeroToProd\Thryds\Tables\Migration;

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
    superglobals: [],
    addKey: '1. Add constant. 2. Reference via Migrator::CONST_NAME where needed.'
)]
readonly class Migrator
{
    // --- Public status key ---

    public const string col_status = 'status';

    // --- Private implementation constants ---

    private const string key_path = 'path';

    private const string key_class = 'class';

    private const string param_id = ':id';

    private const string param_description = ':description';

    private const string param_checksum = ':checksum';

    /** @var array<string, array<string, string>> */
    private array $discovered;

    public function __construct(
        private Database $Database,
        private string $migrations_dir,
        private string $migrations_namespace,
    ) {
        $this->discovered = self::discover($this->migrations_dir, $this->migrations_namespace);
    }

    public function ensureTable(): void
    {
        $this->Database->execute(
            'CREATE TABLE IF NOT EXISTS `' . Migration::tableName() . '` (
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
     * Each row: {Migration::id, Migration::description, col_status, Migration::applied_at, Migration::checksum}
     * col_status value is a MigrationStatus enum case (applied, pending, or modified).
     *
     * @return array<int, array<string, mixed>>
     */
    public function status(): array
    {
        $applied = [];
        foreach ($this->fetchApplied() as $row) {
            $applied[$this->rowStr($row, key: Migration::id)] = $row;
        }

        $result = [];
        foreach ($this->discovered as $id => $info) {
            $checksum = $this->checksum(path: $info[self::key_path]);
            if (isset($applied[$id])) {
                $result[] = [
                    Migration::id          => $id,
                    Migration::description => $info[Migration::description],
                    self::col_status             => $checksum === $applied[$id][Migration::checksum] ? MigrationStatus::applied : MigrationStatus::modified,
                    Migration::applied_at  => $applied[$id][Migration::applied_at],
                    Migration::checksum    => $checksum,
                ];
            } else {
                $result[] = [
                    Migration::id          => $id,
                    Migration::description => $info[Migration::description],
                    self::col_status             => MigrationStatus::pending,
                    Migration::applied_at  => null,
                    Migration::checksum    => $checksum,
                ];
            }
        }

        return $result;
    }

    /**
     * Applies all pending migrations in id order. Throws if any applied migration has been modified.
     *
     * @return list<array{id: string, description: string}> Key names match Migration::id and Migration::description constants.
     */
    public function migrate(): array
    {
        $applied = [];
        foreach ($this->status() as $row) {
            $id = $this->rowStr($row, key: Migration::id);
            if ($row[self::col_status] === MigrationStatus::modified) {
                throw new RuntimeException(
                    "Migration $id was modified after being applied — checksum mismatch. Restore the file or roll back."
                );
            }
            if ($row[self::col_status] !== MigrationStatus::pending) {
                continue;
            }
            $this->runUp(class: $this->discovered[$id][self::key_class]);
            $this->Database->execute(
                'INSERT INTO `' . Migration::tableName() . '` (id, description, checksum, applied_at) VALUES (' . self::param_id . ', ' . self::param_description . ', ' . self::param_checksum . ', NOW())',
                [self::param_id => $id, self::param_description => $row[Migration::description], self::param_checksum => $row[Migration::checksum]]
            );
            $applied[] = [
                Migration::id          => $id,
                Migration::description => $this->rowStr($row, key: Migration::description),
            ];
        }

        return $applied;
    }

    /**
     * Rolls back the most recently applied migration.
     *
     * Returns the rolled-back migration, or null if there was nothing to roll back.
     *
     * @return array{id: string, description: string}|null Key names match Migration::id and Migration::description constants.
     */
    public function rollback(): ?array
    {
        $last = $this->fetchLastApplied();
        if ($last === null) {
            return null;
        }
        $id = $this->rowStr(row: $last, key: Migration::id);
        if (!isset($this->discovered[$id])) {
            throw new RuntimeException("Migration $id is applied but its file was not found in {$this->migrations_dir}.");
        }
        $this->runDown(class: $this->discovered[$id][self::key_class]);
        $this->Database->execute(
            'DELETE FROM `' . Migration::tableName() . '` WHERE id = ' . self::param_id,
            [self::param_id => $id]
        );

        return [
            Migration::id          => $id,
            Migration::description => $this->rowStr(row: $last, key: Migration::description),
        ];
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

    /** @return array<int, array<string, mixed>> */
    private function fetchApplied(): array
    {
        return $this->Database->all($this->selectFromTable('ASC'));
    }

    /** @return array<string, mixed>|null */
    private function fetchLastApplied(): ?array
    {
        return $this->Database->one($this->selectFromTable('DESC', 1));
    }

    private function selectFromTable(string $order, ?int $limit = null): string
    {
        $sql = 'SELECT * FROM `' . Migration::tableName() . '` ORDER BY ' . Migration::id . ' ' . $order;
        if ($limit !== null) {
            $sql .= ' LIMIT ' . $limit;
        }

        return $sql;
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
        if (!is_string($value)) {
            throw new RuntimeException("Expected string for key '$key', got " . gettype($value) . '.'); // @codeCoverageIgnore
        }

        return $value;
    }

    /**
     * Executes the up action for a migration class.
     *
     * Attribute-driven: if #[CreateTable] is present, generates DDL from the target Table class.
     * Imperative fallback: delegates to MigrationInterface::up().
     *
     */
    private function runUp(string $class): void
    {
        $create_table = self::createTableAttribute($class);

        if ($create_table !== null) {
            $this->Database->execute(DdlBuilder::createTableSql($create_table->table));

            return;
        }

        $this->instantiate($class)->up(Database: $this->Database);
    }

    /**
     * Executes the down action for a migration class.
     *
     * Attribute-driven: if #[CreateTable] is present, generates DROP TABLE DDL.
     * Imperative fallback: delegates to MigrationInterface::down().
     *
     */
    private function runDown(string $class): void
    {
        $create_table = self::createTableAttribute($class);

        if ($create_table !== null) {
            $this->Database->execute(DdlBuilder::dropTableSql($create_table->table));

            return;
        }

        $this->instantiate($class)->down(Database: $this->Database);
    }

    private static function createTableAttribute(string $class): ?CreateTable
    {
        /** @var class-string $class Validated by discover() via class_exists(). */
        $attrs = new ReflectionClass(objectOrClass: $class)->getAttributes(CreateTable::class);

        return $attrs !== [] ? $attrs[0]->newInstance() : null;
    }

    private function instantiate(string $class): MigrationInterface
    {
        if (!class_exists($class)) {
            throw new RuntimeException("Migration class $class does not exist."); // @codeCoverageIgnore
        }
        $instance = new $class();
        if (!$instance instanceof MigrationInterface) {
            throw new RuntimeException("$class must implement MigrationInterface.");
        }

        return $instance;
    }
}
