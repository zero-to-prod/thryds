<?php

namespace Test\ForbidClassInheritanceRector;

// TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md
class AlreadyFlagged extends BaseService
{
    public function handle(): void {}
}

?>
