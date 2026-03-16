<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getItems()
{
    return [1, 2, 3];
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getItems(): array
{
    return [1, 2, 3];
}

?>
