<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getNumber()
{
    return 42;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getNumber(): int
{
    return 42;
}

?>
