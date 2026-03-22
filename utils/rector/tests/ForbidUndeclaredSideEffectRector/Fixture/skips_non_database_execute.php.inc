<?php

namespace Utils\Rector\Tests\ForbidUndeclaredSideEffectRector\Fixture;

class SomeOtherService {
    public function run(object $task): void {
        $task->execute();
    }
}
?>
