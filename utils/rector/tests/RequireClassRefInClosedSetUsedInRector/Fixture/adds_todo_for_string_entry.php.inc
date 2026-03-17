<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

#[TestClosedSet(domain: 'routes', usedIn: ['Router::map'])]
enum Route: string
{
    case home = '/';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;

// TODO: [RequireClassRefInClosedSetUsedInRector] Each usedIn entry must be [Class::class, 'method'], not a plain string. Found 'Router::map'.
#[TestClosedSet(domain: 'routes', usedIn: ['Router::map'])]
enum Route: string
{
    case home = '/';
}

?>
