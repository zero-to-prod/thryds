<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

use Random\RandomException;

trait DbCreate
{
    /**
     * @param  mixed  ...$args
     * @throws RandomException
     */
    public static function create(mixed ...$args): void
    {
        /** @phpstan-ignore argument.type (variadic delegation — concrete @method phpdocs on each query class provide the real contract) */
        new self()->handle(...$args);
    }
}
