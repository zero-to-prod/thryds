<?php

namespace Fixture;

class Log
{
    public const string exception = 'exception';
    public const string file = 'file';
    public const string line = 'line';

    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed', [
    'custom_key' => 'some value',
]);

?>
