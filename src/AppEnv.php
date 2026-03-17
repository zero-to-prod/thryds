<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\ClosedSet;
use ZeroToProd\Thryds\Helpers\Domain;

#[ClosedSet(Domain: Domain::application_environment, addCase: '1. Add enum case. 2. Handle in Config::__construct() and App::boot().')]
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
