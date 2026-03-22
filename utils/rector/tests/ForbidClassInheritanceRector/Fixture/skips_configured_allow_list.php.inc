<?php

namespace Test\ForbidClassInheritanceRector;

class AdminService extends AllowedBaseClass
{
    public function execute(): void {}
}

?>
