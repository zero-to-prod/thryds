<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        $format = function (string $value): string {
            return strtoupper($value);
        };
    }
}

?>
