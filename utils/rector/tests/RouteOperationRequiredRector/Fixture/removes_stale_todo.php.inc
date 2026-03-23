<?php

enum TestRoute: string
{
    // TODO: [RouteOperationRequiredRector] Route case 'home' must declare at least one #[RouteOperation] so the inventory graph can emit HTTP methods for this route.
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
}

?>
-----
<?php

enum TestRoute: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
}

?>
