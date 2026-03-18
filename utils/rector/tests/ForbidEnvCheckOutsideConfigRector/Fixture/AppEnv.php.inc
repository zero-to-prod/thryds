<?php

// Env reads inside an AppEnv class are allowed.

$value = $_ENV['APP_KEY'];
$env = getenv('DATABASE_URL');

?>
