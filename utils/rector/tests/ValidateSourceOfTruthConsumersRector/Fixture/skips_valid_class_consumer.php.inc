<?php

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\Fixture;

use Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\TestSourceOfTruth;

#[TestSourceOfTruth(
    for: 'test data',
    consumers: ['Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\ValidConsumer'],
)]
class SkipsValidSource
{
    public const string key = 'key';
}

?>
