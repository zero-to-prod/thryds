<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

#[TestKeyRegistry(source: 'example')]
readonly class Service
{
    public static function handle(): void {}
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

#[TestKeyRegistry(source: 'example')]
readonly class Service
{
    public static function handle(): void {}
}

?>
