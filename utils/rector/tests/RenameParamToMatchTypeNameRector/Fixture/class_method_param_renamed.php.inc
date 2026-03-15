<?php

class Controller
{
    public function handle(Router $r): void
    {
        $r->dispatch();
    }
}

?>
-----
<?php

class Controller
{
    public function handle(Router $Router): void
    {
        $Router->dispatch();
    }
}

?>
