<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector;

class StaleConsumer
{
    public function doSomething(): string
    {
        return 'unrelated';
    }
}
