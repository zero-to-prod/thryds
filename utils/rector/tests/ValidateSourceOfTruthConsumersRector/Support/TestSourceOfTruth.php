<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ValidateSourceOfTruthConsumersRector;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class TestSourceOfTruth
{
    public function __construct(
        public string $for,
        public array $consumers = [],
        public string $addCase = '',
    ) {}
}
