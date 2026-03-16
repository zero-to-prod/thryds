<?php

namespace Fixture;

class OtherClass
{
    public static function error(string $message, array $context = []): void {}
}

OtherClass::error('Something failed', [
    'exception' => 'RuntimeException',
]);

?>
