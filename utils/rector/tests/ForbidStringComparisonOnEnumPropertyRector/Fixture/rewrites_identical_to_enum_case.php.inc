<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv->value === 'production';

?>
-----
<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = $Config->AppEnv === \Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv::production;

?>
