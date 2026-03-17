<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class ClosedSet
{
    /** @param list<array{class-string, string}> $used_in Each entry is [Class::class, 'method'] — AST-refactorable. */
    public function __construct(
        public string $domain,
        public array $used_in = [],
    ) {}
}
