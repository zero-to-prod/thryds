<?php

namespace Fixture;

// TODO: [SuggestEnumForNameEqualsValueConstRector] Enumerations define sets — BladeDirectives has 3 string constants where name equals value. Migrate to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForNameEqualsValueConstRector.md
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
}

?>
