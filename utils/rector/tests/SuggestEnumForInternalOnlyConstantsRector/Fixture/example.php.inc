<?php

namespace Fixture;

readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
        echo self::vite;
    }
}

?>
-----
<?php

namespace Fixture;

// TODO: [SuggestEnumForInternalOnlyConstantsRector] BladeDirectives has 3 string constants only referenced internally — consider migrating to a backed enum.
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';

    public static function register(): void
    {
        echo self::production;
        echo self::env;
        echo self::vite;
    }
}

?>
