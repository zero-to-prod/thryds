<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class MarkedConfig
{
    use DataModel;

    // TODO: $env has known values: 'production' — consider extracting to an enum
    #[Describe(['default' => 'production'])]
    public string $env;
}
