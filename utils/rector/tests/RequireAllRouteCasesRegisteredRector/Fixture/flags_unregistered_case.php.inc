<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
    case unregistered = '/unregistered';
}

?>
-----
<?php

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
    // TODO: [RequireAllRouteCasesRegisteredRector] Route case 'unregistered' is defined but never registered in any router map() call.
    case unregistered = '/unregistered';
}

?>
