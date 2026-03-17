<?php

namespace Fixture;

readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}

?>
-----
<?php

namespace Fixture;

// TODO: [SuggestEnumForNameEqualsValueConstRector] BladeDirectives has 3 string constants where name equals value — consider migrating to a backed enum.
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}

?>
