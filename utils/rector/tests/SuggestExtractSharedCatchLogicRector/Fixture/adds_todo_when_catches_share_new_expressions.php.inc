<?php

namespace Fixture;

class Emitter
{
    public function emit(Response $response): void {}
}

class Response
{
    public function __construct(public string $body) {}
}

class FooException extends \Exception {}

try {
    run();
} catch (FooException $FooException) {
    new Emitter()->emit(new Response($FooException->getMessage()));
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}

?>
-----
<?php

namespace Fixture;

class Emitter
{
    public function emit(Response $response): void {}
}

class Response
{
    public function __construct(public string $body) {}
}

class FooException extends \Exception {}

// TODO: [SuggestExtractSharedCatchLogicRector] Multiple catch blocks instantiate the same classes (Emitter, Response) — extract shared logic to a single declarative source. See: utils/rector/docs/SuggestExtractSharedCatchLogicRector.md
try {
    run();
} catch (FooException $FooException) {
    new Emitter()->emit(new Response($FooException->getMessage()));
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}

?>
