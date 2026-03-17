<?php

$max = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);

?>
-----
<?php

$max = (int) ($_SERVER[\Utils\Rector\Tests\ForbidBareServerEnvKeyRector\Env::MAX_REQUESTS] ?? 0);

?>
