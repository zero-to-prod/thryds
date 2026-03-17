<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;

#[TestNamesKeys(domain: 'Blade directives', used_in: ['Blade::directive'])]
readonly class BladeDirectives
{
    public const string production = 'production';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;

// TODO: [RequireClassRefInClosedSetUsedInRector] Each used_in entry must be [Class::class, 'method'], not a plain string. Found 'Blade::directive'.
#[TestNamesKeys(domain: 'Blade directives', used_in: ['Blade::directive'])]
readonly class BladeDirectives
{
    public const string production = 'production';
}

?>
