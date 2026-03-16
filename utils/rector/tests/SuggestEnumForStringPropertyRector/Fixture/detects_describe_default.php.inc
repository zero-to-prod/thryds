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

    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $env has values: 'production'. Extract to a backed enum.
    #[Describe(['default' => 'production'])]
    public string $env;
}
?>
