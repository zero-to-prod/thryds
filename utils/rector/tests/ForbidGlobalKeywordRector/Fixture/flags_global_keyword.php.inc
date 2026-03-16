<?php

function foo(): void
{
    global $config;
}

?>
-----
<?php

function foo(): void
{
    // TODO: [opcache] global keyword prevents scope-level optimization
    global $config;
}

?>
