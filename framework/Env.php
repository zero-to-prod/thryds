<?php

declare(strict_types=1);

namespace ZeroToProd\Framework;

use ZeroToProd\Framework\Attributes\KeyRegistry;
use ZeroToProd\Framework\Attributes\KeySource;
use ZeroToProd\Framework\Attributes\SourceOfTruth;
use ZeroToProd\Framework\Attributes\SourceOfTruthConcept;

#[SourceOfTruth(
    SourceOfTruthConcept::environment_variable_keys,
    addCase: <<<TEXT
        1. Add constant.
        2. Add to compose.yaml environment section if needed.
        3. Add to .env.example.
    TEXT,
)]
#[KeyRegistry(
    KeySource::server_env,
    superglobals: ['_SERVER', '_ENV'],
    addKey: ''
)]
readonly class Env
{
    public const string APP_ENV = 'APP_ENV';
    public const string MAX_REQUESTS = 'MAX_REQUESTS';
    public const string FRANKENPHP_HOT_RELOAD = 'FRANKENPHP_HOT_RELOAD';
    public const string DB_DRIVER = 'DB_DRIVER';
    public const string DB_HOST = 'DB_HOST';
    public const string DB_PORT = 'DB_PORT';
    public const string DB_DATABASE = 'DB_DATABASE';
    public const string DB_USERNAME = 'DB_USERNAME';
    public const string DB_PASSWORD = 'DB_PASSWORD';
}
