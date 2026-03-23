<?php

enum TestRoute: string
{
    #[RouteOperation(HttpMethod::GET, 'Marketing home page')]
    case home = '/';
    #[RouteOperation(HttpMethod::GET, 'Render login form')]
    #[RouteOperation(HttpMethod::POST, 'Handle login submission')]
    case login = '/login';
}
