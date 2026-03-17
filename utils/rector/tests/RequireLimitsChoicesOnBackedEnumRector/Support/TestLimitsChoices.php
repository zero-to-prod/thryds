<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireLimitsChoicesOnBackedEnumRector;

#[\Attribute(\Attribute::TARGET_CLASS)]
readonly class TestLimitsChoices
{
    public function __construct(
        public string $domain = '',
    ) {}
}
