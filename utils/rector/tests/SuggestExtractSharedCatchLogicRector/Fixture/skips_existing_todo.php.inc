<?php

namespace Fixture;

class FooException extends \Exception {}

// TODO: Multiple catch blocks instantiate the same classes (Emitter, Response) — consider extracting shared logic
try {
    run();
} catch (FooException $FooException) {
    new Emitter()->emit(new Response($FooException->getMessage()));
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}

?>
