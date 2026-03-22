<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

class SomeDatabase {
    public function execute(string $sql, array $params = []): int {
        return 0;
    }

    public function insert(string $sql, array $params = []): string {
        $this->execute($sql, $params);
        return '1';
    }
}
?>
