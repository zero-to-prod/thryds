<?php

namespace Fixture;

class Service
{
    public function handle(): void
    {
        $format = fn(string $value): string => strtoupper($value);
    }
}

?>
