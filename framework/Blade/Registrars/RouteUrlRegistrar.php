<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade\Registrars;

use Tempest\Blade\Blade;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Blade\BladeRegistrar;
use ZeroToProd\Framework\Blade\Vite;
use ZeroToProd\Framework\Config;

#[Infrastructure]
#[BladeRegistrar]
readonly class RouteUrlRegistrar
{
    public function register(string $name, Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->directive($name, static fn(string $expression): string => "<?php echo \ZeroToProd\Framework\Routes\RouteUrl::for({$expression}); ?>");
    }
}
