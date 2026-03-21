<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

trait DbCreate
{
    public static function create(...$args)
    {
        return new self()->handle(...$args);
    }
}
