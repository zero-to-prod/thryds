<?php

class Logger {
    public function log(string $message, bool $verbose = false): void {}
}

$logger = new Logger();
$logger->log('hello', verbose: true);
