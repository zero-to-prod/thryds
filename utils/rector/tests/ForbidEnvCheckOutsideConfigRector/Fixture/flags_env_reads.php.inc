<?php

$a = $_ENV['APP_KEY'];

$b = $_SERVER['APP_ENV'];

$c = getenv('DATABASE_URL');

putenv('FOO=bar');

?>
-----
<?php

// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$a = $_ENV['APP_KEY'];

// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$b = $_SERVER['APP_ENV'];

// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
$c = getenv('DATABASE_URL');

// TODO: [ForbidEnvCheckOutsideConfigRector] Direct env read outside Config boundary. Move to AppEnv or Config class. See: utils/rector/docs/ForbidEnvCheckOutsideConfigRector.md
putenv('FOO=bar');

?>
