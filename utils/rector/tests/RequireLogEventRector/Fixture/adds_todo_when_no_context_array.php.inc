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

// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::error('Something failed');

?>
