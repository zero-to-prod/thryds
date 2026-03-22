<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Thryds\Attributes\InsertsInto;
use ZeroToProd\Thryds\Database;

#[InsertsInto(table: 'SomeTable', columns: ['name'])]
class InsertQuery {
    public function create(Database $Database): void {
        $Database->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    }
}
?>
