<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

#[TestKeyRegistry(source: 'http headers')]
readonly class Header
{
    public const string content_type = 'Content-Type';
    public const string accept = 'Accept';
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

#[TestKeyRegistry(source: 'http headers')]
readonly class Header
{
    public const string content_type = 'Content-Type';
    public const string accept = 'Accept';
}

?>
