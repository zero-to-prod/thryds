<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(Domain::application_environment, addCase: '1. Add enum case. 2. Handle in Config::__construct() and App::boot().')]
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';

    public static function fromEnv(): self
    {
        return self::tryFrom((string) $_SERVER[Env::APP_ENV] ?? $_ENV[Env::APP_ENV] ?? self::production->value) ?? self::production;
    }
}
