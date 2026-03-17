<?php

namespace Fixture;

readonly class SingleConst
{
    public const string foo = 'foo';

    public static function run(): void
    {
        echo self::foo;
    }
}

?>
