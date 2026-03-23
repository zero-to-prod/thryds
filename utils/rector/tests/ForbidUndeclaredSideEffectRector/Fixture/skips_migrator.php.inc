<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Framework\Attributes\MigrationsSource;
use ZeroToProd\Thryds\Database;

#[MigrationsSource(directory: 'migrations')]
class SomeMigrator {
    public function runDdl(Database $Database): void {
        $Database->execute('CREATE TABLE test (id INT)');
    }
}
?>
