<?php

namespace Fixture;

class Log
{
    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed', [
    'exception' => 'RuntimeException',
    'file' => '/app/index.php',
    'line' => 42,
]);

?>
