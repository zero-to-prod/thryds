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

// TODO: [SuggestEnumForKeyRegistryWithMethodsRector] BladeDirectives has #[KeyRegistry] but also contains methods — extract constants to a backed enum with #[ClosedSet].
#[TestKeyRegistry(source: 'blade directives')]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string vite = 'vite';

    public static function register(): void {}
}

?>
