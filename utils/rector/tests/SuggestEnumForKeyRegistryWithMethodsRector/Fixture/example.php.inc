<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

#[TestKeyRegistry(source: 'blade directives')]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string vite = 'vite';

    public static function register(): void {}
}

?>
-----
<?php

use Utils\Rector\Tests\SuggestEnumForKeyRegistryWithMethodsRector\TestKeyRegistry;

// TODO: [SuggestEnumForKeyRegistryWithMethodsRector] Enumerations define sets, attributes define properties — BladeDirectives has #[KeyRegistry] but also contains methods. Extract constants to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForKeyRegistryWithMethodsRector.md
#[TestKeyRegistry(source: 'blade directives')]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string vite = 'vite';

    public static function register(): void {}
}

?>
