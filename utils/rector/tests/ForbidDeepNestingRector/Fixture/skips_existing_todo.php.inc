<?php

namespace Fixture;

class Handler
{
    // TODO: Reduce nesting depth (current: 4, max: 2)
    public function handle(): void
    {
        try {
            if ($a) {
                if ($b) {
                    // ...
                } else {
                    // both branches
                }
            } else {
                // outer else too
            }
        } catch (\Throwable $e) {
            // ...
        }
    }
}

?>
