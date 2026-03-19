<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Home page')]
    case home = '/';
}
