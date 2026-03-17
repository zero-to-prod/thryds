<?php

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\Fixture;

use Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\TestSourceOfTruth;

#[TestSourceOfTruth(
    for: 'test data',
    consumers: ['Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\StaleConsumer'],
)]
class TestSource
{
    public const string key = 'key';
}

?>
-----
<?php

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\Fixture;

use Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\TestSourceOfTruth;

// TODO: [ValidateSourceOfTruthConsumersRector] TestSource declares StaleConsumer as a consumer, but it does not reference TestSource. Update the consumers list.
#[TestSourceOfTruth(
    for: 'test data',
    consumers: ['Utils\\Rector\\Tests\\ValidateSourceOfTruthConsumersRector\\StaleConsumer'],
)]
class TestSource
{
    public const string key = 'key';
}

?>
