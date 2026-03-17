<?php

namespace Fixture;

readonly class PartialRef
{
    public const string foo = 'foo';
    public const string bar = 'bar';

    public static function run(): void
    {
        echo self::foo;
    }
}

?>
