<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;

#[KeyRegistry(
    KeySource::http_headers,
    superglobals: [],
    addKey: '1. Add constant. 2. Reference via Header::NAME where needed.'
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
    public const string hx_request = 'HX-Request';
}
