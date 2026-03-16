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
    // TODO: Global keyword prevents scope-level optimization
    global $config;
}

?>
