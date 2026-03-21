<?php

namespace Fixture;

use Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source\TestHandlesException;

class MyHandler
{
    #[TestHandlesException(\RuntimeException::class)]
    public function handleRuntime(\RuntimeException $Exception): void {}

    public function handleDomain(\DomainException $Exception): void {}
}

?>
-----
<?php

namespace Fixture;

use Utils\Rector\Tests\RequireHandlesExceptionOnPublicHandlerMethodRector\Source\TestHandlesException;

class MyHandler
{
    #[TestHandlesException(\RuntimeException::class)]
    public function handleRuntime(\RuntimeException $Exception): void {}

    // TODO: [RequireHandlesExceptionOnPublicHandlerMethodRector] Public method MyHandler::handleDomain accepts a Throwable subtype but is missing #[HandlesException] — it will never be dispatched. See: utils/rector/docs/RequireHandlesExceptionOnPublicHandlerMethodRector.md
    public function handleDomain(\DomainException $Exception): void {}
}

?>
