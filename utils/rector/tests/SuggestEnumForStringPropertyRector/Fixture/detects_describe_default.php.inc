<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class AppConfig
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $env;
}
?>
-----
<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class AppConfig
{
    use DataModel;

    // TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets. $env has values: 'production' — extract to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
    #[Describe(['default' => 'production'])]
    public string $env;
}
?>
