<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Thryds\Attributes\Migration;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\MigrationInterface;

/**
 * Test fixture migration. Uses DML only (no DDL) so that it can run inside a
 * transaction that is rolled back by DatabaseTestCase::tearDown().
 *
 * The table `_migration_fixture` must already exist — created in
 * MigratorTest::setUpDatabase() as a TEMPORARY TABLE before the transaction opens.
 */
#[Migration(
    id: '0001',
    description: 'Insert fixture row'
)]
final readonly class TestInsertRow implements MigrationInterface
{
    public function up(Database $Database): void
    {
        $Database->execute('INSERT INTO _migration_fixture (id) VALUES (1)');
    }

    public function down(Database $Database): void
    {
        $Database->execute('DELETE FROM _migration_fixture WHERE id = 1');
    }
}
