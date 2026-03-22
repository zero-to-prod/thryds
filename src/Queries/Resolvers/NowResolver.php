<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries\Resolvers;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Queries\PersistResolver;

#[Infrastructure]
readonly class NowResolver implements PersistResolver
{
    public function resolve(mixed $value): string
    {
        return gmdate('Y-m-d H:i:s');
    }
}
