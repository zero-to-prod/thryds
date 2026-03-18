<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Psr\Http\Message\ServerRequestInterface;
use ZeroToProd\Thryds\Attributes\Requirement;

/**
 * Per-request correlation ID. MUST be reset after each request via reset().
 *
 * In FrankenPHP worker mode, PHP state persists across requests. The reset() call
 * in public/index.php's finally block ensures no ID leaks between requests.
 *
 * @see docs/adr/009-hot-reloading.md#consequences-static-state
 */
#[Requirement('TRACE-001', 'SEC-001')]
class RequestId
{
    private static ?string $current = null;

    /** Initialize from an incoming request header or generate a new ID. */
    #[Requirement('TRACE-001')]
    public static function init(ServerRequestInterface $ServerRequestInterface): string
    {
        $header = $ServerRequestInterface->getHeaderLine(Header::request_id);

        self::$current = $header !== '' ? $header : self::generate();

        return self::$current;
    }

    public static function current(): ?string
    {
        return self::$current;
    }

    /** @see docs/adr/009-hot-reloading.md#consequences-static-state */
    #[Requirement('SEC-001')]
    public static function reset(): void
    {
        self::$current = null;
    }

    private static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
