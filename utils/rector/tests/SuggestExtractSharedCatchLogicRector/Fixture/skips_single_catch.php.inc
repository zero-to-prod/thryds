<?php

namespace Fixture;

try {
    run();
} catch (\Throwable $Throwable) {
    new Emitter()->emit(new Response('error'));
}

?>
