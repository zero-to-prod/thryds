<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Database;

use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use ZeroToProd\Thryds\MigrationStatus;
use ZeroToProd\Thryds\Migrator;
use ZeroToProd\Thryds\Tables\Migration;

/**
 * Tests the Migrator infrastructure: table creation, apply, rollback, and
 * checksum-based modification detection.
 *
 * Fixture migrations live in tests/Database/Fixtures/Migrations/ and use only
 * DML (INSERT/DELETE) so they run inside the transaction that DatabaseTestCase
 * rolls back in tearDown(). The `migrations` tracking table itself is created
 * as a session-scoped temporary table in setUpDatabase() — it shadows the real
 * schema for the duration of the test and is discarded on teardown.
 */
final class MigratorTest extends DatabaseTestCase
{
    private const string fixtures_dir = __DIR__ . '/Fixtures/Migrations';

    private const string nonexistent_dir = '/nonexistent/migrations';

    private const string migrations_namespace = 'ZeroToProd\\Thryds\\Migrations\\';

    private const string where_first = ' WHERE ' . Migration::id . " = '0001'";

    private const string count_migrations = 'SELECT COUNT(*) FROM migrations';

    private const string count_fixture = 'SELECT COUNT(*) FROM _migration_fixture';

    private const string tamper_checksum = 'UPDATE migrations SET checksum = \'tampered\'' . self::where_first;

    private Migrator $Migrator;

    protected function setUpDatabase(): void
    {
        // A session-scoped temporary table with the same name shadows the real schema,
        // so ensureTable() finds it via IF NOT EXISTS and all writes are discarded on teardown.
        $this->Database->execute(
            'CREATE TEMPORARY TABLE migrations (
                id          VARCHAR(20)  NOT NULL,
                description VARCHAR(255) NOT NULL,
                checksum    VARCHAR(64)  NOT NULL,
                applied_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );

        // Fixture table for test migrations to INSERT into
        $this->Database->execute(
            'CREATE TEMPORARY TABLE _migration_fixture (id INT NOT NULL)'
        );
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->Migrator = new Migrator(
            Database: $this->Database,
            migrations_dir: self::fixtures_dir,
            migrations_namespace: self::migrations_namespace,
        );
        $this->Migrator->ensureTable();
    }

    #[Test]
    public function ensureTable_is_idempotent(): void
    {
        // ensureTable() was already called in setUp() — a second call must not throw
        $this->Migrator->ensureTable();

        $this->assertSame(0, (int) $this->Database->scalar(self::count_migrations));
    }

    #[Test]
    public function migrate_applies_pending_migration(): void
    {
        $this->assertSame(0, (int) $this->Database->scalar(self::count_migrations));

        $this->Migrator->migrate();

        $this->assertSame(1, (int) $this->Database->scalar(self::count_migrations));
        $this->assertSame(1, (int) $this->Database->scalar('SELECT COUNT(*) FROM ' . Migration::tableName() . self::where_first));
        $this->assertSame(1, (int) $this->Database->scalar(self::count_fixture));
    }

    #[Test]
    public function migrate_stores_sha256_checksum(): void
    {
        $this->Migrator->migrate();

        $row = $this->Database->one('SELECT ' . Migration::checksum . ' FROM ' . Migration::tableName() . self::where_first);
        $this->assertNotNull(actual: $row);
        $this->assertSame(hash('sha256', (string) file_get_contents(self::fixtures_dir . '/0001_TestInsertRow.php')), $row[Migration::checksum]);
    }

    #[Test]
    public function migrate_is_idempotent(): void
    {
        $this->Migrator->migrate();
        $this->Migrator->migrate();

        // Applied once, not twice
        $this->assertSame(1, (int) $this->Database->scalar(self::count_migrations));
        $this->assertSame(1, (int) $this->Database->scalar(self::count_fixture));
    }

    #[Test]
    public function rollback_calls_down_and_removes_row(): void
    {
        $this->Migrator->migrate();
        $this->assertSame(1, (int) $this->Database->scalar(self::count_fixture));

        $this->Migrator->rollback();

        $this->assertSame(0, (int) $this->Database->scalar(self::count_migrations));
        $this->assertSame(0, (int) $this->Database->scalar(self::count_fixture));
    }

    #[Test]
    public function status_returns_pending_before_migrate(): void
    {
        $rows = $this->Migrator->status();

        $this->assertCount(1, haystack: $rows);
        $this->assertSame(MigrationStatus::pending, $rows[0][Migrator::col_status]);
        $this->assertSame('0001', $rows[0][Migration::id]);
        $this->assertNull($rows[0][Migration::applied_at]);
    }

    #[Test]
    public function status_returns_applied_after_migrate(): void
    {
        $this->Migrator->migrate();

        $rows = $this->Migrator->status();

        $this->assertCount(1, haystack: $rows);
        $this->assertSame(MigrationStatus::applied, $rows[0][Migrator::col_status]);
        $this->assertNotNull($rows[0][Migration::applied_at]);
    }

    #[Test]
    public function status_detects_modified_checksum(): void
    {
        $this->Migrator->migrate();

        // Tamper with the stored checksum to simulate a modified file
        $this->Database->execute(
            self::tamper_checksum
        );

        $this->assertSame(MigrationStatus::modified, $this->Migrator->status()[0][Migrator::col_status]);
    }

    #[Test]
    public function migrate_throws_on_modified_migration(): void
    {
        $this->Migrator->migrate();
        $this->Database->execute(self::tamper_checksum);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/0001.*modified/');

        $this->Migrator->migrate();
    }

    #[Test]
    public function rollback_returns_null_when_nothing_applied(): void
    {
        $this->assertNull($this->Migrator->rollback());
    }

    #[Test]
    public function rollback_throws_when_applied_migration_file_not_found(): void
    {
        $this->Migrator->migrate();

        $Migrator = new Migrator(
            Database: $this->Database,
            migrations_dir: self::nonexistent_dir,
            migrations_namespace: self::migrations_namespace,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/applied but its file was not found/');

        $Migrator->rollback();
    }

    #[Test]
    public function status_returns_empty_when_no_migration_files_exist(): void
    {
        $this->assertSame([], new Migrator(
            Database: $this->Database,
            migrations_dir: self::nonexistent_dir,
            migrations_namespace: self::migrations_namespace,
        )->status());
    }

    #[Test]
    public function discover_skips_files_without_valid_class_or_attribute(): void
    {
        $this->assertSame([], new Migrator(
            Database: $this->Database,
            migrations_dir: __DIR__ . '/Fixtures/MigrationsSkip',
            migrations_namespace: self::migrations_namespace,
        )->status());
    }

    #[Test]
    public function discover_throws_when_migration_attribute_id_mismatches_filename(): void
    {
        $Migrator = new Migrator(
            Database: $this->Database,
            migrations_dir: __DIR__ . '/Fixtures/MigrationsWrongId',
            migrations_namespace: self::migrations_namespace,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/does not match filename prefix/');

        $Migrator->status();
    }

    #[Test]
    public function migrate_throws_when_migration_class_does_not_implement_interface(): void
    {
        $Migrator = new Migrator(
            Database: $this->Database,
            migrations_dir: __DIR__ . '/Fixtures/MigrationsNotInterface',
            migrations_namespace: self::migrations_namespace,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/must implement MigrationInterface/');

        $Migrator->migrate();
    }
}
