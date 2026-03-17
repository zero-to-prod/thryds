<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

#[TestClosedSet(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

#[TestClosedSet(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
