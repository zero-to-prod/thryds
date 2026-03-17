<?php

enum TestRoute: string
{
    // TODO: [RequireRouteTestRector] Route case 'home' has no corresponding test. Add a test that exercises this route.
    case home = '/';
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
}

?>
