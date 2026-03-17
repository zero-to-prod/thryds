<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\NamesKeys;

#[NamesKeys(
    source: '$_SERVER / $_ENV',
    access: '$_SERVER[Env::KEY] ?? $_ENV[Env::KEY]',
    superglobals: ['_SERVER', '_ENV'],
)]
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
}
