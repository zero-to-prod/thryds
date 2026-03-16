<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        $callback = function () {
            if ($a) {
                if ($b) {
                    if ($c) {
                        if ($d) {
                            doSomething();
                        }
                    }
                }
            }
        };
    }
}

?>
