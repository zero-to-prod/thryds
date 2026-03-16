<?php

$router = new League\Route\Router();

?>
-----
<?php

// TODO: [ForbidDirectRouterInstantiationRector] Use League\Route\Cache\Router instead of instantiating League\Route\Router directly. Direct instantiation bypasses route caching.
$router = new League\Route\Router();

?>
