<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class FlagConfig
{
    use DataModel;

    #[Describe(['default' => true])]
    public bool $debug;

    public int $count;
}
