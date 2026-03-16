<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function identity(string $value)
{
    return $value;
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function identity(string $value): string
{
    return $value;
}

?>
