<?php

namespace Fixture;

// TODO: [SuggestEnumForInternalOnlyConstantsRector] AlreadyCommented has 2 string constants only referenced internally — consider migrating to a backed enum.
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
