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

    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $env has values: 'production'. Extract to a backed enum.
    #[Describe(['default' => 'production'])]
    public string $env;
}

$WebConfig = WebConfig::from([
    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. 'production' is a value of WebConfig::$env. Replace with enum case.
    WebConfig::env => $_ENV['APP_ENV'] ?? 'production',
]);

?>
