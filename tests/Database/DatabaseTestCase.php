<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Database;

use PDOException;
use PHPUnit\Framework\TestCase;
use ZeroToProd\Thryds\Database;
use ZeroToProd\Thryds\DatabaseConfig;

/**
 * Base class for database tests. Each test runs inside a transaction that is
 * rolled back in tearDown, leaving the database clean for the next test.
 *
 * Override setUpDatabase() to run DDL (CREATE TEMPORARY TABLE, etc.) before
 * the transaction opens — MySQL DDL causes an implicit commit, so it must
 * happen before beginTransaction().
 */
abstract class DatabaseTestCase extends TestCase
{
    protected Database $Database;

    protected function setUp(): void
    {
        try {
            $this->Database = new Database(DatabaseConfig::fromEnv());
        } catch (PDOException $e) {
            $this->markTestSkipped('db container not running — start it with: docker compose up -d db (' . $e->getMessage() . ')');
        }
        $this->setUpDatabase();
        $this->Database->beginTransaction();
    }

    /** Run DDL or other pre-transaction setup here. Default: no-op. */
    protected function setUpDatabase(): void {}

    protected function tearDown(): void
    {
        if ($this->Database->inTransaction()) {
            $this->Database->rollBack();
        }
    }
}
