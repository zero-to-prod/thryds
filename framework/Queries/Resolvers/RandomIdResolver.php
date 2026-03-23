<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries\Resolvers;

use Random\RandomException;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Queries\PersistResolver;

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
