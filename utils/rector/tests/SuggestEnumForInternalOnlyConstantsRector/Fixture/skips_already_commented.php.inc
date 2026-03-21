<?php

namespace Fixture;

// TODO: [SuggestEnumForInternalOnlyConstantsRector] Enumerations define sets — AlreadyCommented has 2 string constants only referenced internally. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForInternalOnlyConstantsRector.md
readonly class AlreadyCommented
{
    public const string foo = 'foo';
    public const string bar = 'bar';

    public static function run(): void
    {
        echo self::foo;
        echo self::bar;
    }
}

?>
