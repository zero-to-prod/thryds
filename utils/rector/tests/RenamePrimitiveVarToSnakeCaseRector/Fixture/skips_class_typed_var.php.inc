<?php

class MyService {}

function makeService(): MyService
{
    return new MyService();
}

$myService = makeService();
$myService->doSomething();

?>
