<?php

namespace Fixture;

class Log
{
    public const string exception = 'exception';
    public const string file = 'file';
    public const string line = 'line';

    public static function debug(string $message, array $context = []): void {}
    public static function info(string $message, array $context = []): void {}
    public static function warn(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
}

Log::debug('debug', ['exception' => 'E']);
Log::info('info', ['file' => '/tmp']);
Log::warn('warn', ['line' => 1]);

?>
-----
<?php

namespace Fixture;

class Log
{
    public const string exception = 'exception';
    public const string file = 'file';
    public const string line = 'line';

    public static function debug(string $message, array $context = []): void {}
    public static function info(string $message, array $context = []): void {}
    public static function warn(string $message, array $context = []): void {}
    public static function error(string $message, array $context = []): void {}
}

Log::debug('debug', [\Fixture\Log::exception => 'E']);
Log::info('info', [\Fixture\Log::file => '/tmp']);
Log::warn('warn', [\Fixture\Log::line => 1]);

?>
