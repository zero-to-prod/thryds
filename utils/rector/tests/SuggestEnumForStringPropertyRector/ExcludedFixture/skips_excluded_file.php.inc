<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class ExcludedConfig
{
    use DataModel;

    #[Describe(['default' => 'production'])]
    public string $env;
}

$ExcludedConfig = ExcludedConfig::from([
    'env' => 'production',
]);
