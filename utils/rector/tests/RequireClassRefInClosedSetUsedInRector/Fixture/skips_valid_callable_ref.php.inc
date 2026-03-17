<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;
use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestRouter;

#[TestClosedSet(domain: 'routes', used_in: [[TestRouter::class, 'map']])]
enum Route: string
{
    case home = '/';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestClosedSet;
use Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector\TestRouter;

#[TestClosedSet(domain: 'routes', used_in: [[TestRouter::class, 'map']])]
enum Route: string
{
    case home = '/';
}

?>
