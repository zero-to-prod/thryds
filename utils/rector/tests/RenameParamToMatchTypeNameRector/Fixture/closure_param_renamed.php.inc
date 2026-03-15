<?php

$fn = function (Router $r): void {
    $r->dispatch();
};

?>
-----
<?php

$fn = function (Router $Router): void {
    $Router->dispatch();
};

?>
