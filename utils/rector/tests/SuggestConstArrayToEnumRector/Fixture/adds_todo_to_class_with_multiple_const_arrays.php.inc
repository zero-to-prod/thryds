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

// TODO: [SuggestConstArrayToEnumRector] Enumerations define sets — migrate const arrays to a backed enum with #[ClosedSet] and #[Group] attributes. See: utils/rector/docs/SuggestConstArrayToEnumRector.md
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
