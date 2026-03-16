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

    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $mode has values: 'development', 'production'. Extract to a backed enum.
    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}
?>
