<?php

enum TestRoute: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    // TODO: [RouteOperationRequiredRector] Route case 'about' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.
    case about = '/about';
}

?>
