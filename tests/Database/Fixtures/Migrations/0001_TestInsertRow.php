<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Migrations;

use ZeroToProd\Framework\Attributes\Migration;
use ZeroToProd\Framework\Attributes\RawSql;

/**
 * Test fixture migration. Uses DML only (no DDL) so that it can run inside a
 * transaction that is rolled back by the test teardown method.
 *
 * The table `_migration_fixture` must already exist — created as a
 * TEMPORARY TABLE in the test setup before the transaction opens.
 */
#[Migration(
    id: '0001',
    description: 'Insert fixture row'
)]
#[RawSql(
    up: 'INSERT INTO _migration_fixture (id) VALUES (1)',
    down: 'DELETE FROM _migration_fixture WHERE id = 1',
)]
final readonly class TestInsertRow {}
