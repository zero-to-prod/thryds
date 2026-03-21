<?php

namespace Fixture;

use Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source\TestHandlesException;

class HandlerWithNonThrowableMethod
{
    #[TestHandlesException(\RuntimeException::class)]
    public function handleRuntime(\RuntimeException $Exception): void {}

    public function configure(string $value): void {}
}

?>
