<?php

$handler = getHandler();
$fn = static function () use ($handler): void {
    $handler->run();
};

?>
