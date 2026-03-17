<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;

#[TestNamesKeys(domain: 'opcache_get_status()')]
readonly class OpcacheStatus
{
    public const string scripts = 'scripts';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector\TestNamesKeys;

#[TestNamesKeys(domain: 'opcache_get_status()')]
readonly class OpcacheStatus
{
    public const string scripts = 'scripts';
}

?>
