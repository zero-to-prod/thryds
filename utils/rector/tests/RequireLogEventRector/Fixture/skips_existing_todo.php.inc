<?php

namespace Fixture;

class Log
{
    public const string exception = 'exception';

    public static function error(string $message, array $context = []): void {}
}

// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::error('Something failed', [
    Log::exception => 'RuntimeException',
]);

?>
