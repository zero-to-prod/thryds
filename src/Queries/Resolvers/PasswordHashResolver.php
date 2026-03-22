<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries\Resolvers;

use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Queries\PersistResolver;

#[Infrastructure]
readonly class PasswordHashResolver implements PersistResolver
{
    public function resolve(mixed $value): string
    {
        return password_hash(is_string($value) ? $value : '', PASSWORD_BCRYPT);
    }
}
