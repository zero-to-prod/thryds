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

    // TODO: [AI] Property $env has known string values: 'production'. Consider extracting to a backed enum with these cases, then type-hint this property with the enum.
    #[Describe(['default' => 'production'])]
    public string $env;
}

$WebConfig = WebConfig::from([
    // TODO: [AI] The string 'production' is a known value of WebConfig::$env. Once an enum is created for $env, replace this with the enum case.
    WebConfig::env => $_ENV['APP_ENV'] ?? 'production',
]);

?>
