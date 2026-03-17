<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

use Jenssegers\Blade\Blade;
use ZeroToProd\Thryds\AppEnv;
use ZeroToProd\Thryds\Config;
use ZeroToProd\Thryds\Env;

#[NamesKeys(
    source: 'Blade directive names',
    access: '$Blade->directive(BladeDirectives::KEY, ...)',
)]
readonly class BladeDirectives
{
    public const string production = 'production';
    public const string env = 'env';
    public const string vite = 'vite';
    public const string htmx = 'htmx';
    public const string hotReload = 'hotReload';

    public static function register(Blade $Blade, Config $Config, Vite $Vite): void
    {
        $Blade->if(self::production, fn(): bool => $Config->AppEnv === AppEnv::production);
        $Blade->if(self::env, fn(string ...$environments): bool => in_array($Config->AppEnv->value, haystack: $environments, strict: true));

        $Blade->directive(self::vite, static fn(): string => $Vite->directivePhp(Vite::app_entry));
        $Blade->directive(self::htmx, static fn(): string => $Vite->directivePhp(Vite::htmx_entry));
        $Blade->directive(self::hotReload, static fn(): string => '<?php if (isset($_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'])): ?>'
            . '<meta name="frankenphp-hot-reload:url" content="<?= $_SERVER[\'' . Env::FRANKENPHP_HOT_RELOAD . '\'] ?>">'
            . '<script src="https://cdn.jsdelivr.net/npm/idiomorph" defer></script>'
            . '<script src="https://cdn.jsdelivr.net/npm/frankenphp-hot-reload/+esm" type="module"></script>'
            . '<?php endif; ?>');
    }
}
