<?php

readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

?>
-----
<?php

// TODO: [RequireNamesKeysOnConstantsClassRector] CacheKey contains only string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class CacheKey
{
    public const string user_profile = 'user_profile';
    public const string session = 'session';
}

?>
