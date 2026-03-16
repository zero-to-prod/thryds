<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class MarkedConfig
{
    use DataModel;

    // TODO: [SuggestEnumForStringPropertyRector] Enums limit choices. $env has values: 'production'. Extract to a backed enum.
    #[Describe(['default' => 'production'])]
    public string $env;
}
