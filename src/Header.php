<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Psr\Http\Message\MessageInterface;
use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: 'HTTP headers',
    used_in: [[MessageInterface::class, 'getHeaderLine']],
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}
