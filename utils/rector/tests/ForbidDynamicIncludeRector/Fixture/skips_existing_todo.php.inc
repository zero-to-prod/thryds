<?php

$path = 'foo.php';
// TODO: [opcache] dynamic include prevents OPcache optimization
require $path;

?>
