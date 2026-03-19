<?php

enum TestRoute: string
{
    // TODO: [RouteOperationRequiresRouteInfoRector] Route case 'home' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together.
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Home page')]
    case home = '/';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Home page')]
    case home = '/';
}

?>
