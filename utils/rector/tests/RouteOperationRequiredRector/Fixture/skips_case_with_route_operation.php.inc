<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    #[RouteInfo('Login')]
    #[RouteOperation(HttpMethod::GET, 'Render login form')]
    #[RouteOperation(HttpMethod::POST, 'Handle login submission')]
    case login = '/login';
}
