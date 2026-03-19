<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RemoveDefaultsAndApplyAtCallsiteRector\Support;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestKeyRegistry
{
    public function __construct(
        public string $source,
        public array $superglobals = [],
        public string $addKey = '',
    ) {}
}
