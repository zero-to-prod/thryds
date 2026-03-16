<?php

namespace Fixture;

class Log
{
    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed', [
    'exception' => 'RuntimeException',
    'file' => '/app/index.php',
]);

?>
-----
<?php

namespace Fixture;

class Log
{
    public static function error(string $message, array $context = []): void {}
}

Log::error('Something failed', [
    \Fixture\Log::exception => 'RuntimeException',
    \Fixture\Log::file => '/app/index.php',
]);

?>
