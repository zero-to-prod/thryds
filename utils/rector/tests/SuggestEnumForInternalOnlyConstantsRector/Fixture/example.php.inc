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

// TODO: [SuggestEnumForInternalOnlyConstantsRector] Enumerations define sets — BladeDirectives has 3 string constants only referenced internally. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md
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
