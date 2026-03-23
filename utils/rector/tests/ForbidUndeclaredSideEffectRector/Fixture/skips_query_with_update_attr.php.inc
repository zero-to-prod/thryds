<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Framework\Attributes\UpdatesIn;
use ZeroToProd\Thryds\Database;

#[UpdatesIn(table: 'SomeTable', columns: ['name'], where: ['id'])]
class UpdateQuery {
    public function update(Database $Database): void {
        $Database->execute('UPDATE users SET name = ? WHERE id = ?', ['Alice', 1]);
    }
}
?>
