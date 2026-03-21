<?php

namespace Utils\Rector\Tests\RequireHandlesExceptionParamMatchRector\Fixture;

use ZeroToProd\Thryds\Attributes\HandlesException;

class CorrectHandler
{
    #[HandlesException(\Throwable::class)]
    public function handleThrowable(\Throwable $Throwable): void {}
}

?>
