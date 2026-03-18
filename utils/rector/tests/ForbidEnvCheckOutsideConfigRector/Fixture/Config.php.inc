<?php

// Env reads inside a Config class are allowed.

$value = $_ENV['APP_KEY'];
$env = getenv('DATABASE_URL');
putenv('FOO=bar');

?>
