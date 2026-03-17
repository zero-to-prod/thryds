<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\NamesKeys;

#[NamesKeys(
    source: 'HTTP headers',
    access: '$request->getHeaderLine(Header::KEY)',
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}
