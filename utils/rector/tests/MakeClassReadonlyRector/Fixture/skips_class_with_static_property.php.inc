<?php

class Logger
{
    private static array $context = [];

    public static function log(string $message): void
    {
        echo $message;
    }
}

?>
