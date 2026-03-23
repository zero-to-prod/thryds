<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Queries\Resolvers;

use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Queries\PersistResolver;

#[Infrastructure]
#[PersistResolver]
readonly class PasswordHashResolver
{
    public function resolve(mixed $value): string
    {
        return password_hash(is_string($value) ? $value : '', PASSWORD_BCRYPT);
    }
}
