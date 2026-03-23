<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade\Registrars;

use Tempest\Blade\Blade;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Blade\BladeRegistrar;
use ZeroToProd\Thryds\Blade\Vite;
use ZeroToProd\Thryds\Config;

#[Infrastructure]
#[BladeRegistrar]
readonly class ViteRegistrar
{
    public function register(string $name, Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->directive($name, static fn(): string => $Vite->directivePhp(Vite::app_entry));
    }
}
