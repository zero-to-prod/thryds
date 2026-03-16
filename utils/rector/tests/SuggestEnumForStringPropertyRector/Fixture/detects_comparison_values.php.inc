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

    // TODO: [AI] Property $mode has known string values: 'development', 'production'. Consider extracting to a backed enum with these cases, then type-hint this property with the enum.
    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}
?>
