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

    // TODO: $env has known values: 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $env;
}
?>
