<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Framework\Attributes\DeletesFrom;
use ZeroToProd\Thryds\Database;

#[DeletesFrom(table: 'SomeTable', where: ['id'])]
class DeleteQuery {
    public function delete(Database $Database): void {
        $Database->execute('DELETE FROM users WHERE id = ?', [1]);
    }
}
?>
