<?php

namespace Fixture;

class FooException extends \Exception {}

try {
    run();
} catch (FooException $FooException) {
    new Logger()->log($FooException->getMessage());
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}

?>
