<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
    case untested = '/untested';
}

?>
-----
<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireRouteTestRector] Route case 'untested' has no corresponding test. Add a test that exercises this route.
    case untested = '/untested';
}

?>
