<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Jenssegers\Blade\Blade;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Env;

readonly class BladeDirectives
{
    public static function register(Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->if(BladeDirective::production->value, fn(): bool => $Config->AppEnv === AppEnv::production);
        $Blade->if(BladeDirective::env->value, fn(string ...$environments): bool => in_array($Config->AppEnv->value, haystack: $environments, strict: true));

        $Blade->directive(BladeDirective::vite->value, static fn(): string => $Vite->directivePhp(Vite::app_entry));
        $Blade->directive(BladeDirective::htmx->value, static fn(): string => $Vite->directivePhp(Vite::htmx_entry));
        $Blade->directive(BladeDirective::hotReload->value, static fn(): string => '<?php if (isset($_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'])): ?>'
            . '<meta name="frankenphp-hot-reload:url" content="<?= $_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'] ?>">'
            . '<script src="https://cdn.jsdelivr.net/npm/idiomorph" defer></script>'
            . '<script src="https://cdn.jsdelivr.net/npm/frankenphp-hot-reload/+esm" type="module"></script>'
            . '<?php endif; ?>');
    }
}
