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

// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::debug('debug msg');
// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::info('info msg');
// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::warn('warn msg');
// TODO: [RequireLogEventRector] Log calls need a durable event id. Add `Log::event => Log::<event_label>` to the context array.
Log::error('error msg');

?>
