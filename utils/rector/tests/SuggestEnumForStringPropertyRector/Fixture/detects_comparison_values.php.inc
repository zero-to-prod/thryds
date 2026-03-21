<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class ServerConfig
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}
?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class ServerConfig
{
    use DataModel;

    // TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets. $mode has values: 'development', 'production' — extract to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}
?>
