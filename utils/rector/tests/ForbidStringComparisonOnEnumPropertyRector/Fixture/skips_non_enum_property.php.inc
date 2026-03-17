<?php

class Config { public string $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv === 'production';
