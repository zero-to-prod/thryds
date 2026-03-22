<?php

namespace Fixture;

readonly class DataConfig
{
    public const string host = 'host';
    public const string port = 'port';
    public const string name = 'name';

    public string $host;
    public int $port;
    public string $name;

    public static function fromEnv(): self
    {
        $instance = new self();
        echo self::host;
        echo self::port;
        echo self::name;

        return $instance;
    }
}

?>
