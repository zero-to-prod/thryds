<?php

namespace Fixture;

readonly class DevFilter
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
-----
<?php

namespace Fixture;

// TODO: Consider migrating const arrays to a backed enum with #[Group] attributes
readonly class DevFilter
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
