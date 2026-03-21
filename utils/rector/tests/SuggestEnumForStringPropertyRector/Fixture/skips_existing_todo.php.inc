<?php

use Zerotoprod\DataModel\DataModel;
use Zerotoprod\DataModel\Describe;

class MarkedConfig
{
    use DataModel;

    // TODO: [SuggestEnumForStringPropertyRector] Enumerations define sets. $env has values: 'production' — extract to a backed enum with #[ClosedSet]. See: utils/rector/docs/SuggestEnumForStringPropertyRector.md
    #[Describe(['default' => 'production'])]
    public string $env;
}
