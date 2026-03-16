<?php

$Router->map('GET', '/about', $handler);

?>
-----
<?php

$Router->map('GET', \Fixture\Routes\AboutRoute::pattern, $handler);

?>
