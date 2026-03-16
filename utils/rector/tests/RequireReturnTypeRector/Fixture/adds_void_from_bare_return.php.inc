<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function earlyExit(bool $flag)
{
    if ($flag) {
        return;
    }
}

?>
-----
<?php

namespace Utils\Rector\Tests\RequireReturnTypeRector\Fixture;

function earlyExit(bool $flag): void
{
    if ($flag) {
        return;
    }
}

?>
