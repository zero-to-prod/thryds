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
 * All migrations are attribute-driven via classes carrying #[MigrationAction]
 * (#[CreateTable], #[AddColumn], #[DropColumn], #[RawSql]).
 *
 * Transaction behavior depends on the active driver:
 * - MySQL: DDL causes implicit commit; migrations run without wrapping.
 * - PostgreSQL/SQLite: DDL is transactional; Migrator wraps up/down in a transaction.
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
        $this->Database->execute(DdlBuilder::createTableSql(Migration::class, $this->Database->driver()));
    }

    /**
     * Returns one row per migration file, ordered by id.
     *
     * @return list<MigrationStatusRow>
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
            if ($row->MigrationStatus === MigrationStatus::modified) {
                throw new RuntimeException(
                    "Migration $row->id was modified after being applied — checksum mismatch. Restore the file or roll back."
                );
            }
            if ($row->MigrationStatus !== MigrationStatus::pending) {
                continue;
            }
            $this->runUp(class: $this->MigrationDiscovery->fqcn($row->id));
            InsertMigrationQuery::create((object) [
                Migration::id          => $row->id,
                Migration::description => $row->description,
                Migration::checksum    => $row->checksum,
            ], $this->Database);
            $applied[] = [
                Migration::id          => $row->id,
                Migration::description => $row->description,
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
     * Dispatches on any attribute class carrying #[MigrationAction].
     */
    private function runUp(string $class): void
    {
        /** @var class-string $class Validated by discover() via class_exists(). */
        $sql = self::resolveMigrationAction($class)->upSql(); // @phpstan-ignore method.notFound (MigrationAction marker guarantees upSql())

        if ($this->Database->driver()->transactionalDdl()) {
            $this->Database->transaction(static fn(Database $Database): int => $Database->execute($sql));
        } else {
            $this->Database->execute($sql);
        }
    }

    /**
     * Executes the down action for a migration class.
     *
     * Dispatches on any attribute class carrying #[MigrationAction].
     */
    private function runDown(string $class): void
    {
        /** @var class-string $class Validated by discover() via class_exists(). */
        $sql = self::resolveMigrationAction($class)->downSql(); // @phpstan-ignore method.notFound (MigrationAction marker guarantees downSql())

        if ($this->Database->driver()->transactionalDdl()) {
            $this->Database->transaction(static fn(Database $Database): int => $Database->execute($sql));
        } else {
            $this->Database->execute($sql);
        }
    }

    /**
     * Resolves the first attribute carrying #[MigrationAction] from a migration class.
     *
     * @param class-string $class
     */
    private static function resolveMigrationAction(string $class): object
    {
        foreach (new ReflectionClass(objectOrClass: $class)->getAttributes() as $attribute) {
            $ReflectionClass = new ReflectionClass($attribute->getName());
            if ($ReflectionClass->getAttributes(MigrationAction::class) !== []) {
                return $attribute->newInstance();
            }
        }

        throw new RuntimeException(
            "$class must declare a MigrationAction attribute (#[CreateTable], #[AddColumn], #[DropColumn], or #[RawSql])."
        );
    }
}
