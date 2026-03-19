<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    #[RouteInfo('About')]
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    // TODO: [RouteOperationRequiredRector] Route case 'about' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.
    #[RouteInfo('About')]
    case about = '/about';
}

?>
