<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries\Resolvers;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Queries\PersistResolver;

#[Infrastructure]
#[PersistResolver]
readonly class NowResolver
{
    public function resolve(mixed $value): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
