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

    // TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets. $env has values: 'production' — extract to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
    #[Describe(['default' => 'production'])]
    public string $env;
}

$WebConfig = WebConfig::from([
    // TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets. 'production' is a value of WebConfig::$env — replace with an enum case. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
    WebConfig::env => $_ENV['APP_ENV'] ?? 'production',
]);

?>
