<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv->value === 'staging';
