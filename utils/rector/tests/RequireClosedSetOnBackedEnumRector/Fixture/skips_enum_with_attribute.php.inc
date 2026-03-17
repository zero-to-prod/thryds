<?php

use Utils\Rector\Tests\RequireClosedSetOnBackedEnumRector\TestClosedSet;

#[TestClosedSet(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClosedSetOnBackedEnumRector\TestClosedSet;

#[TestClosedSet(domain: 'permissions')]
enum Permission: string
{
    case read = 'read';
    case write = 'write';
}

?>