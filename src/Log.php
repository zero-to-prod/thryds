<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

class Log
{
    private static array $context = [];

    public static function withContext(array $context): void
    {
        self::$context = array_merge(self::$context, $context);
    }

    public static function withoutContext(): void
    {
        self::$context = [];
    }

    public static function debug(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Debug->value, array_merge(self::$context, $context));
    }

    public static function info(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Info->value, array_merge(self::$context, $context));
    }

    public static function warn(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Warn->value, array_merge(self::$context, $context));
    }

    public static function error(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Error->value, array_merge(self::$context, $context));
    }
}
