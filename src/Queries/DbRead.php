<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

trait DbRead
{
    /**
     * @return mixed
     */
    public static function read(...$args)
    {
        return new self()->handle(...$args);
    }
}
