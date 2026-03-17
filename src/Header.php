<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use Psr\Http\Message\MessageInterface;
use ZeroToProd\Thryds\Helpers\NamesKeys;

#[NamesKeys(
    domain: 'HTTP headers',
    used_in: [[MessageInterface::class, 'getHeaderLine']],
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}
