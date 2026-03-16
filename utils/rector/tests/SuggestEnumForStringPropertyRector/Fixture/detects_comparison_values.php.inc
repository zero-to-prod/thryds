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

    // TODO: $mode has known values: 'development', 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $mode;

    public static function isDev(mixed $value, array $context): bool
    {
        return ($context['mode'] ?? 'production') === 'development';
    }
}
?>
