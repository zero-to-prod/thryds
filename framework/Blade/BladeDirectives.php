<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade;

use Tempest\Blade\Blade;
use ZeroToProd\Framework\Attributes\Infrastructure;
use ZeroToProd\Framework\Config;

#[Infrastructure]
readonly class BladeDirectives
{
    public static function register(Blade $Blade, Config $Config, Vite $Vite): void
    {
        foreach (BladeDirective::cases() as $Directive) {
            $Directive->register($Blade, $Config, $Vite);
        }
    }
}
