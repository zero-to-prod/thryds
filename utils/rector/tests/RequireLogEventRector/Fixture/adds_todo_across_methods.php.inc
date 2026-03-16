<?php

namespace Fixture;

class Log
{
    public const string event = 'event';

    public static function debug(string $message, array $context = []): void {}
    public static function info(string $message, array $context = []): void {}
    public static function warn(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
}

Log::debug('debug msg');
Log::info('info msg');
Log::warn('warn msg');
Log::error('error msg');

?>
-----
<?php

namespace Fixture;

class Log
{
    public const string event = 'event';

    public static function debug(string $message, array $context = []): void {}
    public static function info(string $message, array $context = []): void {}
    public static function warn(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
}

// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::debug('debug msg');
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::info('info msg');
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::warn('warn msg');
// TODO: Add a durable event identifier — `Log::event => Log::<event_label>`
Log::error('error msg');

?>
