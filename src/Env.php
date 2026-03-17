<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\KeyRegistry;
use ZeroToProd\Thryds\Helpers\SourceOfTruth;

#[SourceOfTruth(
    for: 'environment variable keys',
    consumers: [
        App::class,
        'public/index.php',
    ],
    addCase: '1. Add constant. 2. Add to compose.yaml environment section if needed. 3. Add to .env.example.',
)]
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
