<?php

$Router->map('GET', '/about', $handler);

?>
-----
<?php

$Router->map('GET', \ZeroToProd\Thryds\Routes\AboutRoute::pattern, $handler);

?>
