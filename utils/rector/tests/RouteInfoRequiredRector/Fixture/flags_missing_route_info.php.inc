<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    case home = '/';
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    case home = '/';
    // TODO: [RouteInfoRequiredRector] Route case 'about' must declare #[RouteInfo] so the inventory graph can emit a description for this route.
    case about = '/about';
}

?>
