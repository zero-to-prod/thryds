<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireNamesKeysOnMixedConstantsClassRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestNamesKeys
{
    public function __construct(
        public string $source = '',
    ) {}
}
