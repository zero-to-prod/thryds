<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Per-request correlation ID. MUST be reset after each request via reset().
 *
 * In FrankenPHP worker mode, PHP state persists across requests. The reset() call
 * in public/index.php's finally block ensures no ID leaks between requests.
 */
class RequestId
{
    public const string header = 'X-Request-ID';

    private static ?string $current = null;

    /** Initialize from an incoming request header or generate a new ID. */
    public static function init(ServerRequestInterface $ServerRequestInterface): string
    {
        $header = $ServerRequestInterface->getHeaderLine(self::header);

        self::$current = $header !== '' ? $header : self::generate();

        return self::$current;
    }

    public static function current(): ?string
    {
        return self::$current;
    }

    public static function reset(): void
    {
        self::$current = null;
    }

    private static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
