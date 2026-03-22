<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use Tempest\Blade\Blade;
use ZeroToProd\Thryds\Attributes\Infrastructure;
use ZeroToProd\Thryds\Config;

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
