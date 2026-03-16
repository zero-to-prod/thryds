<?php

namespace Fixture;

class Log
{
    public const string event = 'event';

    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed');

?>
-----
<?php

namespace Fixture;

class Log
{
    public const string event = 'event';

    public static function error(string $message, array $context = []): void {}
}

// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::error('Something failed');

?>
