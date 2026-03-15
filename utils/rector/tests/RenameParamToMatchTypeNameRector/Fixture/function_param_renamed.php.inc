<?php

function handle(Router $r): void
{
    $r->dispatch();
}

?>
-----
<?php

function handle(Router $Router): void
{
    $Router->dispatch();
}

?>
