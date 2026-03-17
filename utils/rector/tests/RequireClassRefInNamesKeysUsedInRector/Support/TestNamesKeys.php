<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireClassRefInNamesKeysUsedInRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestNamesKeys
{
    /** @param list<array{class: class-string, method: string}> $used_in */
    public function __construct(
        public string $domain = '',
        public array $used_in = [],
    ) {}
}
