<?php

namespace Test\ForbidClassInheritanceRector;

class UserService extends BaseService
{
    public function find(int $id): string
    {
        return '';
    }
}

?>
-----
<?php

namespace Test\ForbidClassInheritanceRector;

// TODO: [ForbidClassInheritanceRector] Inheritance couples classes to parent implementation — use PHP attributes and composition instead. See: utils/rector/docs/ForbidClassInheritanceRector.md
class UserService extends BaseService
{
    public function find(int $id): string
    {
        return '';
    }
}

?>
