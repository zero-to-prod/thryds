<?php

declare(strict_types=1);

namespace ZeroToProd\Framework;

use Psr\Http\Message\ServerRequestInterface;
use ZeroToProd\Framework\Attributes\Requirement;

/**
 * Per-request correlation ID. MUST be reset after each request via reset().
 *
 * In FrankenPHP worker mode, PHP state persists across requests. The reset() call
 * in public/index.php's finally block ensures no ID leaks between requests.
 *
 * @see SEC-001
 */
#[Requirement(
    'TRACE-001',
    'SEC-001'
)]
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

    /** @see SEC-001 */
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
