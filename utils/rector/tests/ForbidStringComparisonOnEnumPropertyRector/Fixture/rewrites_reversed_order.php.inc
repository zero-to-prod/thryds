<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = 'production' === $Config->AppEnv->value;

?>
-----
<?php

use Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv;

class Config { public TestAppEnv $AppEnv; }

$Config = new Config();
$result = \Utils\Rector\Tests\ForbidStringComparisonOnEnumPropertyRector\TestAppEnv::production === $Config->AppEnv;

?>
