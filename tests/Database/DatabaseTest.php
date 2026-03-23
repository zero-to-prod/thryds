<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Database;

use PDOException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ZeroToProd\Framework\Database;
use ZeroToProd\Framework\DatabaseConfig;

final class DatabaseTest extends TestCase
{
    private const string insert_sql = 'INSERT INTO _test_db (val) VALUES (?)';
    private const string count_sql = 'SELECT COUNT(*) FROM _test_db WHERE val = ?';
    private const string committed = 'committed';
    private const string closure_result = 'closure-result';
    private const string should_not_persist = 'should-not-persist';
    private const string oops = 'oops';

    private Database $Database;

    protected function setUp(): void
    {
        try {
            $this->Database = new Database(DatabaseConfig::fromEnv());
            $this->Database->execute('CREATE TEMPORARY TABLE _test_db (id INT NOT NULL AUTO_INCREMENT PRIMARY KEY, val VARCHAR(64))');
            $this->Database->beginTransaction();
        } catch (PDOException $e) {
            $this->markTestSkipped('db container not running — start it with: docker compose up -d db (' . $e->getMessage() . ')');
        }
    }

    protected function tearDown(): void
    {
        if ($this->Database->inTransaction()) {
            $this->Database->rollBack();
        }
    }

    #[Test]
    public function insert_returns_last_insert_id(): void
    {
        $this->assertSame('1', $this->Database->insert(self::insert_sql, ['hello']));
    }

    #[Test]
    public function transaction_commits_and_returns_closure_result(): void
    {
        // Roll back the outer test transaction so transaction() can manage its own.
        $this->Database->rollBack();

        $this->assertSame(self::closure_result, $this->Database->transaction(function (Database $Database): string {
            $Database->execute(self::insert_sql, [self::committed]);
            return self::closure_result;
        }));
        $this->assertSame(1, (int) $this->Database->scalar(self::count_sql, [self::committed]));
    }

    #[Test]
    public function transaction_rolls_back_and_rethrows_on_exception(): void
    {
        // Roll back the outer test transaction so transaction() can manage its own.
        $this->Database->rollBack();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(self::oops);

        try {
            $this->Database->transaction(function (Database $Database): void {
                $Database->execute(self::insert_sql, [self::should_not_persist]);
                throw new RuntimeException(self::oops);
            });
        } finally {
            $this->assertSame(0, (int) $this->Database->scalar(self::count_sql, [self::should_not_persist]));
        }
    }

    #[Test]
    public function run_rethrows_non_gone_away_pdo_exceptions(): void
    {
        $this->expectException(PDOException::class);
        $this->Database->scalar('NOT VALID SQL');
    }

    #[Test]
    public function run_reconnects_and_retries_after_gone_away(): void
    {
        // Roll back the outer test transaction — KILL CONNECTION causes an
        // implicit disconnect, which would leave a dangling transaction.
        $this->Database->rollBack();

        // Kill the current connection from a second Database instance.
        new Database(DatabaseConfig::fromEnv())->execute('KILL CONNECTION ' . (int) $this->Database->scalar('SELECT CONNECTION_ID()'));

        // The first instance should transparently reconnect and return 1.
        $this->assertSame('1', (string) $this->Database->scalar('SELECT 1'));
    }
}
