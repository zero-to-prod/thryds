<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class WebConfig
{
    use DataModel;

    public const string env = 'env';

    #[Describe(['default' => 'production'])]
    public string $env;
}

$WebConfig = WebConfig::from([
    WebConfig::env => $_ENV['APP_ENV'] ?? 'production',
]);

?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class WebConfig
{
    use DataModel;

    public const string env = 'env';

    // TODO: $env has known values: 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $env;
}

$WebConfig = WebConfig::from([
    // TODO: 'production' is a value of WebConfig::$env — consider replacing with an enum case
    WebConfig::env => $_ENV['APP_ENV'] ?? 'production',
]);

?>
