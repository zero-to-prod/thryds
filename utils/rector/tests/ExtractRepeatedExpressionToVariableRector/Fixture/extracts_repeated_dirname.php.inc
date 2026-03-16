<?php

require dirname(__DIR__) . '/vendor/autoload.php';
$a = dirname(__DIR__) . '/var/cache';
$b = dirname(__DIR__) . '/templates';
?>
-----
<?php

$baseDir = dirname(__DIR__);
require $baseDir . '/vendor/autoload.php';
$a = $baseDir . '/var/cache';
$b = $baseDir . '/templates';
?>
