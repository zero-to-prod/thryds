<?php

enum TestRoute: string
{
    case home = '/';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'about' is defined but never registered in any router map() call.
    case about = '/about';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'login' is defined but never registered in any router map() call.
    case login = '/login';
}
-----
<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
    case login = '/login';
}
