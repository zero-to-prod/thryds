<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class User
{
    public function __construct(public string $name) {}
}

function createUser(string $name)
{
    return new User($name);
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class User
{
    public function __construct(public string $name) {}
}

function createUser(string $name): \Utils\Rector\Tests\RequireReturnTypeRector\Fixture\User
{
    return new User($name);
}

?>
