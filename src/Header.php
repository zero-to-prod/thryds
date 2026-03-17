<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: 'HTTP headers',
    addKey: '1. Add constant. 2. Reference via Header::NAME where needed.',
)]
readonly class Header
{
    public const string request_id = 'X-Request-ID';
}
