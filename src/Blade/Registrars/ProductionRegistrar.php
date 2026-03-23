<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade\Registrars;

use Tempest\Blade\Blade;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Blade\BladeRegistrar;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;

#[Infrastructure]
#[BladeRegistrar]
readonly class ProductionRegistrar
{
    public function register(string $name, Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->if($name, fn(): bool => $Config->AppEnv === AppEnv::production);
    }
}
