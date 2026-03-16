<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class NullableUser
{
    public function __construct(public int $id) {}
}

function findUser(int $id)
{
    if ($id <= 0) {
        return null;
    }
    return new NullableUser($id);
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

class NullableUser
{
    public function __construct(public int $id) {}
}

function findUser(int $id): ?\Utils\Rector\Tests\RequireReturnTypeRector\Fixture\NullableUser
{
    if ($id <= 0) {
        return null;
    }
    return new NullableUser($id);
}

?>
