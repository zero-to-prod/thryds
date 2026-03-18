<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

readonly class Log
{
    public static function debug(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Debug->value, self::withRequestId($context));
    }

    public static function info(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Info->value, self::withRequestId($context));
    }

    public static function warn(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Warn->value, self::withRequestId($context));
    }

    public static function error(string $message, array $context = []): void
    {
        frankenphp_log($message, LogLevel::Error->value, self::withRequestId($context));
    }

    private static function withRequestId(array $context): array
    {
        $id = RequestId::current();

        if ($id !== null) {
            $context[LogContext::request_id] = $id;
        }

        return $context;
    }
}
