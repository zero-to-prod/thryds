<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ReflectionClass;
use RuntimeException;
use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;
use ZeroToProd\Thryds\Attributes\MigrationAction;
use ZeroToProd\Thryds\Attributes\MigrationsSource;
use ZeroToProd\Thryds\Queries\DeleteMigrationQuery;
use ZeroToProd\Thryds\Queries\InsertMigrationQuery;
use ZeroToProd\Thryds\Queries\SelectLastMigrationQuery;
use ZeroToProd\Thryds\Schema\DdlBuilder;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * Applies and rolls back database migrations.
 *
 * Orchestrates MigrationDiscovery (filesystem scanning) and
 * MigrationStatusResolver (status computation) to keep each concern
 * in a single-responsibility class.
 *
 * All migrations are attribute-driven via {@see MigrationAction} implementations
 * (#[CreateTable], #[AddColumn], #[DropColumn], #[RawSql]).
 *
 * DDL note: ensureTable() and migration actions that run DDL
 * (CREATE TABLE, ALTER TABLE, etc.) cause MySQL to implicitly commit any open
 * transaction. Call ensureTable() before opening a transaction.
 */
#[MigrationsSource(
    directory: 'migrations',
    namespace: 'ZeroToProd\\Thryds\\Migrations',
)]
#[KeyRegistry(
    KeySource::migrations_table,
    superglobals: [],
    addKey: '1. Add constant. 2. Reference via Migrator::CONST_NAME where needed.'
)]
readonly class Migrator
{
    use RowAccess;

    // --- Public status key ---

    public const string col_status = 'status';

    private MigrationDiscovery $MigrationDiscovery;

    private MigrationStatusResolver $MigrationStatusResolver;

    public function __construct(
        private Database $Database,
        string $migrations_dir,
        string $migrations_namespace,
    ) {
        $this->MigrationDiscovery = new MigrationDiscovery($migrations_dir, $migrations_namespace);
        $this->MigrationStatusResolver = new MigrationStatusResolver($this->MigrationDiscovery, $this->Database);
    }

    /**
     * Builds a Migrator from the #[MigrationsSource] attribute on this class.
     *
     * @param string $base_dir Absolute path to the project root.
     */
    public static function create(Database $Database, string $base_dir): self
    {
        /** @var MigrationsSource $MigrationsSource */
        $MigrationsSource = new ReflectionClass(self::class)
            ->getAttributes(MigrationsSource::class)[0]
            ->newInstance();

        return new self(
            $Database,
            migrations_dir: $base_dir . '/' . $MigrationsSource->directory,
            migrations_namespace: $MigrationsSource->namespace . '\\',
        );
    }

    public function ensureTable(): void
    {
        $this->Database->execute(DdlBuilder::createTableSql(Migration::class));
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
        return $this->MigrationStatusResolver->status();
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
            $this->runUp(class: $this->MigrationDiscovery->fqcn($id));
            InsertMigrationQuery::create((object) [
                Migration::id          => $id,
                Migration::description => $row[Migration::description],
                Migration::checksum    => $row[Migration::checksum],
            ], $this->Database);
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
        $last = SelectLastMigrationQuery::oneRow($this->Database);
        if ($last === null) {
            return null;
        }
        $id = $this->rowStr(row: $last, key: Migration::id);
        if (!$this->MigrationDiscovery->has($id)) {
            throw new RuntimeException("Migration $id is applied but its file was not found.");
        }
        $this->runDown(class: $this->MigrationDiscovery->fqcn($id));
        DeleteMigrationQuery::delete($id, $this->Database);

        return [
            Migration::id          => $id,
            Migration::description => $this->rowStr(row: $last, key: Migration::description),
        ];
    }

    /**
     * Executes the up action for a migration class.
     *
     * Dispatches on any attribute implementing {@see MigrationAction}.
     */
    private function runUp(string $class): void
    {
        /** @var class-string $class Validated by discover() via class_exists(). */
        $this->Database->execute(self::resolveMigrationAction($class)->upSql());
    }

    /**
     * Executes the down action for a migration class.
     *
     * Dispatches on any attribute implementing {@see MigrationAction}.
     */
    private function runDown(string $class): void
    {
        /** @var class-string $class Validated by discover() via class_exists(). */
        $this->Database->execute(self::resolveMigrationAction($class)->downSql());
    }

    /**
     * Resolves the first {@see MigrationAction} attribute from a migration class.
     *
     * @param class-string $class
     */
    private static function resolveMigrationAction(string $class): MigrationAction
    {
        foreach (new ReflectionClass(objectOrClass: $class)->getAttributes() as $attribute) {
            $instance = $attribute->newInstance();
            if ($instance instanceof MigrationAction) {
                return $instance;
            }
        }

        throw new RuntimeException(
            "$class must declare a MigrationAction attribute (#[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql])."
        );
    }
}
