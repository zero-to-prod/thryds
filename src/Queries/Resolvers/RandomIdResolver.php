<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries\Resolvers;

use Random\RandomException;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Queries\PersistResolver;

#[Infrastructure]
#[PersistResolver]
readonly class RandomIdResolver
{
    /** @throws RandomException */
    public function resolve(mixed $value): string
    {
        return substr(bin2hex(random_bytes(13)), 0, 26);
    }
}
