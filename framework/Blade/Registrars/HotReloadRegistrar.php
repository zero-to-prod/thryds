<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade\Registrars;

use Tempest\Blade\Blade;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Blade\BladeRegistrar;
use ZeroToProd\Framework\Blade\Vite;
use ZeroToProd\Framework\Config;
use ZeroToProd\Framework\Env;

#[Infrastructure]
#[BladeRegistrar]
readonly class HotReloadRegistrar
{
    public function register(string $name, Blade $Blade, Config $Config, Vite $Vite): void
    {
        // FRANKENPHP_HOT_RELOAD is read inside the generated PHP string, not captured at registration time.
        // The value is injected by Caddy per-connection and must be evaluated at each render,
        // not once at boot. Do not hoist this to a variable outside the generated string.
        $Blade->directive($name, static fn(): string => '<?php if (isset($_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'])): ?>'
            . '<meta name="frankenphp-hot-reload:url" content="<?= $_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'] ?>">'
            . '<script src="https://cdn.jsdelivr.net/npm/idiomorph" defer></script>'
            . '<script src="https://cdn.jsdelivr.net/npm/frankenphp-hot-reload/+esm" type="module"></script>'
            . '<?php endif; ?>');
    }
}
