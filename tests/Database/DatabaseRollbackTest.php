<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Tests\Database;

use PHPUnit\Framework\Attributes\Test;

// TODO: [SuggestDuplicateStringConstantRector] Refactor duplicate string 'SELECT COUNT(*) FROM _test_rollback' (used 2x) to a single source of truth. Consts name things, enums limit choices, attributes define properties. See: utils/rector/docs/SuggestDuplicateStringConstantRector.md
final class DatabaseRollbackTest extends DatabaseTestCase
{
    protected function setUpDatabase(): void
    {
        // CREATE TEMPORARY TABLE before the transaction opens — DDL in MySQL
        // causes an implicit commit, so it must happen outside a transaction.
        $this->Database->execute('CREATE TEMPORARY TABLE _test_rollback (id INT NOT NULL)');
    }

    #[Test]
    public function inserts_within_a_test_are_rolled_back(): void
    {
        $this->Database->execute('INSERT INTO _test_rollback VALUES (1)');
        $this->assertSame(1, (int) $this->Database->scalar('SELECT COUNT(*) FROM _test_rollback'));

        // Simulate what tearDown does.
        $this->Database->rollBack();

        // Row is gone — proves the transaction was not committed.
        $this->assertSame(0, (int) $this->Database->scalar('SELECT COUNT(*) FROM _test_rollback'));
    }
}
