<?php

namespace Fixture;

class NonReadonly
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
