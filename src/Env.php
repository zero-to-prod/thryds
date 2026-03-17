<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\KeyRegistry;

#[KeyRegistry(
    source: '$_SERVER / $_ENV',
    superglobals: ['_SERVER', '_ENV'],
)]
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
}
