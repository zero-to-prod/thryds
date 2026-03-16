<?php

enum TestRoute: string
{
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'home' is defined but never registered in any router map() call.
    case home = '/';
    case about = '/about';
}

?>
-----
<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
}

?>
