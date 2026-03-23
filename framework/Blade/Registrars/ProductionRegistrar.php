<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade\Registrars;

use Tempest\Blade\Blade;
use ZeroToProd\Framework\AppEnv;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Blade\BladeRegistrar;
use ZeroToProd\Framework\Blade\Vite;
use ZeroToProd\Framework\Config;

#[Infrastructure]
#[BladeRegistrar]
readonly class ProductionRegistrar
{
    public function register(string $name, Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->if($name, fn(): bool => $Config->AppEnv === AppEnv::production);
    }
}
