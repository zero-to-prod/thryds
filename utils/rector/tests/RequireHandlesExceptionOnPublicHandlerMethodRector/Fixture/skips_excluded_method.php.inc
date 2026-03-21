<?php

namespace Fixture;

use Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source\TestHandlesException;

class HandlerWithDispatcher
{
    #[TestHandlesException(\RuntimeException::class)]
    public function handleRuntime(\RuntimeException $Exception): void {}

    public function handle(\Throwable $Throwable): void {}
}

?>
