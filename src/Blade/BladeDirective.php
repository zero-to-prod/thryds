<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use Tempest\Blade\Blade;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Env;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::blade_directives,
    addCase: <<<TEXT
    1. Add enum case.
    2. Add match arm in register().
    TEXT
)]
enum BladeDirective: string
{
    case production = 'production';
    case env = 'env';
    case vite = 'vite';
    case htmx = 'htmx';
    case hotReload = 'hotReload';

    public function register(Blade $Blade, Config $Config, Vite $Vite): void
    {
        match ($this) {
            self::production => $Blade->if($this->value, fn(): bool => $Config->AppEnv === AppEnv::production),
            self::env => $Blade->if($this->value, fn(string ...$environments): bool => in_array($Config->AppEnv->value, haystack: $environments, strict: true)),
            self::vite => $Blade->directive($this->value, static fn(): string => $Vite->directivePhp(Vite::app_entry)),
            self::htmx => $Blade->directive($this->value, static fn(): string => $Vite->directivePhp(Vite::htmx_entry)),
            // FRANKENPHP_HOT_RELOAD is read inside the generated PHP string, not captured at registration time.
            // The value is injected by Caddy per-connection and must be evaluated at each render,
            // not once at boot. Do not hoist this to a variable outside the generated string.
            self::hotReload => $Blade->directive($this->value, static fn(): string => '<?php if (isset($_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'])): ?>'
                . '<meta name="frankenphp-hot-reload:url" content="<?= $_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'] ?>">'
                . '<script src="https://cdn.jsdelivr.net/npm/idiomorph" defer></script>'
                . '<script src="https://cdn.jsdelivr.net/npm/frankenphp-hot-reload/+esm" type="module"></script>'
                . '<?php endif; ?>'),
        };
    }
}
