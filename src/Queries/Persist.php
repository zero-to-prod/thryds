<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::persistence_hooks,
    addCase: 'Add enum case. Implement resolve() match arm.'
)]
enum Persist: string
{
    case random_id     = 'random_id';
    case password_hash = 'password_hash';
    case now           = 'now';

    /** @throws RandomException */
    public function resolve(mixed $value): string
    {
        return match ($this) {
            self::random_id     => substr(bin2hex(random_bytes(13)), 0, 26),
            self::password_hash => password_hash(is_string($value) ? $value : '', PASSWORD_BCRYPT),
            self::now           => gmdate('Y-m-d H:i:s'),
        };
    }
}
