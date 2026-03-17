<?php

$env = $_ENV['APP_ENV'] ?? 'production';

?>
-----
<?php

$env = $_ENV[\Utils\Rector\Tests\ForbidBareServerEnvKeyRector\Env::APP_ENV] ?? 'production';

?>
