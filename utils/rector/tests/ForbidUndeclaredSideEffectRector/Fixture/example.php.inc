<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Thryds\Database;

class SomeService {
    public function save(Database $Database): void {
        $Database->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    }
}
?>
-----
<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

use ZeroToProd\Thryds\Database;

// TODO: [ForbidUndeclaredSideEffectRector] Side-effecting call must be inside a query class with #[InsertsInto], #[UpdatesIn], or #[DeletesFrom]. See: utils/rector/docs/ForbidUndeclaredSideEffectRector.md
class SomeService {
    public function save(Database $Database): void {
        $Database->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    }
}
?>
