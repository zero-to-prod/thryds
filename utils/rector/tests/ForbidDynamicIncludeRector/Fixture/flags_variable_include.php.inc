<?php

$path = 'foo.php';
require $path;

?>
-----
<?php

$path = 'foo.php';
// TODO: [opcache] dynamic include prevents OPcache optimization
require $path;

?>
