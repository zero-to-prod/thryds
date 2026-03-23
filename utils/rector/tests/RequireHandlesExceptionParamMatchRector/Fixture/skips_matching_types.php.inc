<?php

namespace Utils\Rector\Tests\RequireHandlesExceptionParamMatchRector\Fixture;

use ZeroToProd\Framework\Attributes\HandlesException;

class CorrectHandler
{
    #[HandlesException(\Throwable::class)]
    public function handleThrowable(\Throwable $Throwable): void {}
}

?>
