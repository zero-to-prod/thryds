<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries\Resolvers;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Queries\PersistResolver;

#[Infrastructure]
#[PersistResolver]
readonly class NowResolver
{
    public function resolve(mixed $value): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
