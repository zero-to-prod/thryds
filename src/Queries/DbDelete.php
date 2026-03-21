<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Queries;

trait DbDelete
{
    /**
     * @return mixed
     */
    public static function delete(...$args)
    {
        return new self()->handle(...$args);
    }
}
