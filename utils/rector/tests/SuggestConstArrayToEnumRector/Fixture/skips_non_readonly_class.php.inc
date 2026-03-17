<?php

namespace Fixture;

class NonReadonlyFilter
{
    public const array dev_vendors = [
        '/vendor/phpunit/',
        '/vendor/phpstan/',
    ];

    public const array excluded_dirs = [
        '/var/cache/',
        '/tests/',
    ];
}

?>
