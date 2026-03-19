<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Home page')]
    case home = '/';
    #[RouteOperation(HttpMethod::GET, 'About page')]
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Home page')]
    case home = '/';
    // TODO: [RouteOperationRequiresRouteInfoRector] Route case 'about' declares #[RouteOperation] but is missing #[RouteInfo]. Both attributes are required together.
    #[RouteOperation(HttpMethod::GET, 'About page')]
    case about = '/about';
}

?>
