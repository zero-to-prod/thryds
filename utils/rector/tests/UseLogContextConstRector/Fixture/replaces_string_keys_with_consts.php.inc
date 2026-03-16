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
    'exception' => 'RuntimeException',
    'file' => '/app/index.php',
    'line' => 42,
]);

?>
-----
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
    \Fixture\Log::exception => 'RuntimeException',
    \Fixture\Log::file => '/app/index.php',
    \Fixture\Log::line => 42,
]);

?>
