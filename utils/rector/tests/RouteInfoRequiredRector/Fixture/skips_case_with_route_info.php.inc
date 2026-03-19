<?php

enum TestRoute: string
{
    #[RouteInfo('Home')]
    case home = '/';
    #[RouteInfo('About')]
    case about = '/about';
}
