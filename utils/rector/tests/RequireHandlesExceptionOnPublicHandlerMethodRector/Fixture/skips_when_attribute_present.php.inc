<?php

namespace Fixture;

use Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source\TestHandlesException;

class FullyAnnotatedHandler
{
    #[TestHandlesException(\RuntimeException::class)]
    public function handleRuntime(\RuntimeException $Exception): void {}

    #[TestHandlesException(\Throwable::class)]
    public function handleThrowable(\Throwable $Throwable): void {}
}

?>
