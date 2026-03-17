<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$is_dev = $Config->AppEnv->value !== 'development';

?>
-----
<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$is_dev = $Config->AppEnv !== \Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv::development;

?>
