<?php

namespace Fixture;

class Log
{
    public const string exception = 'exception';

    public static function error(string $message, array $context = []): void {}
}

// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::error('Something failed', [
    Log::exception => 'RuntimeException',
]);

?>
