<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;
use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestRouter;

#[TestClosedSet(domain: 'routes', usedIn: [[TestRouter::class, 'map']])]
enum Route: string
{
    case home = '/';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;
use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestRouter;

#[TestClosedSet(domain: 'routes', usedIn: [[TestRouter::class, 'map']])]
enum Route: string
{
    case home = '/';
}

?>
