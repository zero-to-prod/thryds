<?php

enum TestRoute: string
{
    // TODO: [RouteInfoRequiredRector] Route case 'home' must declare #[RouteInfo] so the inventory graph can emit a description for this route.
    #[RouteInfo('Home')]
    case home = '/';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    case home = '/';
}

?>
