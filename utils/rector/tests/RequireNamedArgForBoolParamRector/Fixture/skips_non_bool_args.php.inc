<?php

class Formatter {
    public function format(string $input, int $width, string $align): string
    {
        return $input;
    }
}

$formatter = new Formatter();
$formatter->format('hello', 80, 'left');
