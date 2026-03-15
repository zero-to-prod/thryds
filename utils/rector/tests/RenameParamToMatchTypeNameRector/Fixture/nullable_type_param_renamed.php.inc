<?php

function handle(?Router $r): void
{
    if ($r !== null) {
        $r->dispatch();
    }
}

?>
-----
<?php

function handle(?Router $Router): void
{
    if ($Router !== null) {
        $Router->dispatch();
    }
}

?>
