<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class TaggedService
{
    public const string a = 'a';
    public const string b = 'b';
    public const string c = 'c';

    public static function run(): void {}
}

?>
-----
<?php

use Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector\TestNamesKeys;

#[TestNamesKeys(source: 'test')]
readonly class TaggedService
{
    public const string a = 'a';
    public const string b = 'b';
    public const string c = 'c';

    public static function run(): void {}
}

?>
