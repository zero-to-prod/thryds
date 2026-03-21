<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

trait DbUpdate
{
    /**
     * @return mixed
     */
    public static function update(...$args)
    {
        return new self()->handle(...$args);
    }
}
