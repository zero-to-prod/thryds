<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS)]
readonly class LimitsChoices
{
    public function __construct(
        public string $domain = '',
    ) {}
}
