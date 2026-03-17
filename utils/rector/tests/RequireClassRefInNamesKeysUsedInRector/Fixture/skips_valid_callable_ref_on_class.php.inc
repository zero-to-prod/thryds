<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;
use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestBlade;

#[TestNamesKeys(domain: 'Blade directives', used_in: [[TestBlade::class, 'directive']])]
readonly class BladeDirectives
{
    public const string production = 'production';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;
use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestBlade;

#[TestNamesKeys(domain: 'Blade directives', used_in: [[TestBlade::class, 'directive']])]
readonly class BladeDirectives
{
    public const string production = 'production';
}

?>
