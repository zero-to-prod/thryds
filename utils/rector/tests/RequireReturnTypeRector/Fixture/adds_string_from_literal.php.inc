<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getGreeting()
{
    return 'hello';
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function getGreeting(): string
{
    return 'hello';
}

?>
