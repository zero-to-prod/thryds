<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireNamesKeysOnConstantsClassRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestNamesKeys
{
    public function __construct(
        public string $source = '',
    ) {}
}
