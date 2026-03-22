<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

class ServiceWithUntypedDb {
    public function save($db): void {
        $db->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    }
}
?>
-----
<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

// TODO: [ForbidUndeclaredSideEffectRector] Side-effecting call must be inside a query class with #[InsertsInto], #[UpdatesIn], or #[DeletesFrom]. See: utils/rector/docs/ForbidUndeclaredSideEffectRector.md
class ServiceWithUntypedDb {
    public function save($db): void {
        $db->execute('INSERT INTO users (name) VALUES (?)', ['Alice']);
    }
}
?>
