<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireClassRefInClosedSetUsedInRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestClosedSet
{
    /** @param list<array{class: class-string, method: string}> $usedIn */
    public function __construct(
        public string $domain = '',
        public array $usedIn = [],
    ) {}
}
