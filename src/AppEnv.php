<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\BladeDirectives;
use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(domain: 'application environment', used_in: [[Config::class, '__construct'], [BladeDirectives::class, 'register'], [App::class, 'boot']])]
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
