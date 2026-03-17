<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

#[TestClosedSet(domain: 'routes', used_in: ['Router::map'])]
enum Route: string
{
    case home = '/';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

// TODO: [RequireClassRefInClosedSetUsedInRector] Each used_in entry must be [Class::class, 'method'], not a plain string. Found 'Router::map'.
#[TestClosedSet(domain: 'routes', used_in: ['Router::map'])]
enum Route: string
{
    case home = '/';
}

?>
