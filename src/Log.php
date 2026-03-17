<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\NamesKeys;

#[NamesKeys(
    domain: 'Log context array',
    used_in: [[self::class, 'error']],
)]
readonly class Log
{
    public const string event = 'event';
    public const string exception = 'exception';
    public const string file = 'file';
    public const string line = 'line';
    public const string request_id = 'request_id';
    public const string unhandled_exception = 'unhandled_exception';

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
            $context[self::request_id] = $id;
        }

        return $context;
    }
}
