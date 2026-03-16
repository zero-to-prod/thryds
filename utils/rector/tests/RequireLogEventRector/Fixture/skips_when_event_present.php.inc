<?php

namespace Fixture;

class Log
{
    public const string event = 'event';
    public const string event_explanation = 'event_explanation';

    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed', [
    Log::event => Log::event_explanation,
]);

?>
