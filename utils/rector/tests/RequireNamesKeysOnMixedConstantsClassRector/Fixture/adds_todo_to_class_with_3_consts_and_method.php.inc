<?php

readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}

?>
-----
<?php

// TODO: [RequireNamesKeysOnMixedConstantsClassRector] Metrics has 3 string constants — add #[NamesKeys] to declare what they name (ADR-007).
readonly class Metrics
{
    public const string duration = 'duration';
    public const string status = 'status';
    public const string endpoint = 'endpoint';

    public static function record(): void {}
}

?>
