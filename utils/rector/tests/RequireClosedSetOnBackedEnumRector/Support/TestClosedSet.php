<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireClosedSetOnBackedEnumRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestClosedSet
{
    public function __construct(
        public string $domain = '',
    ) {}
}
