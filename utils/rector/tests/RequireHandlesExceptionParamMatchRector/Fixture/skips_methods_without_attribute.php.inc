<?php

namespace Utils\Rector\Tests\RequireHandlesExceptionParamMatchRector\Fixture;

class NoAttributeHandler
{
    public function handleSomething(\Throwable $Throwable): void {}
}

?>
