<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector;

// References SkipsValidSource for SourceOfTruth validation
class ValidConsumer
{
    /** @see \Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector\Fixture\SkipsValidSource */
    public function doSomething(): string
    {
        return 'SkipsValidSource';
    }
}
