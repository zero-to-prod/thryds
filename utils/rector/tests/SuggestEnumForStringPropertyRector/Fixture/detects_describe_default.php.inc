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

    // TODO: [AI] Property $env has known string values: 'production'. Consider extracting to a backed enum with these cases, then type-hint this property with the enum.
    #[Describe(['default' => 'production'])]
    public string $env;
}
?>
