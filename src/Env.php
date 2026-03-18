<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\KeyRegistry;
use ZeroToProd\Thryds\Attributes\KeySource;
use ZeroToProd\Thryds\Attributes\SourceOfTruth;
use ZeroToProd\Thryds\Attributes\SourceOfTruthConcept;

#[SourceOfTruth(
    SourceOfTruthConcept::environment_variable_keys,
    addCase: '1. Add constant. 2. Add to compose.yaml environment section if needed. 3. Add to .env.example.',
)]
#[KeyRegistry(
    KeySource::server_env,
    superglobals: ['_SERVER', '_ENV'],
)]
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
}
