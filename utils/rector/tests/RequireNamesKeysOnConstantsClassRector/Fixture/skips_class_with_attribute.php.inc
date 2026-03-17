<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class Tagged
{
    public const string key = 'key';
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class Tagged
{
    public const string key = 'key';
}

?>
