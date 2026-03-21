<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::config_keys,
    addCase: <<<TEXT
        1. Add enum case matching the Config property name.
        2. Add a corresponding public property on Config with a #[Describe] attribute.
    TEXT,
)]
enum ConfigKey: string
{
    case AppEnv = 'AppEnv';
    case blade_cache_dir = 'blade_cache_dir';
    case template_dir = 'template_dir';
    case DatabaseConfig = 'DatabaseConfig';
}
