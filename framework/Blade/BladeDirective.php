<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Blade;

use ReflectionEnumBackedCase;
use Tempest\Blade\Blade;
use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Framework\Attributes\ResolvesTo;
use ZeroToProd\Framework\Blade\Registrars\EnvRegistrar;
use ZeroToProd\Framework\Blade\Registrars\HotReloadRegistrar;
use ZeroToProd\Framework\Blade\Registrars\HtmxRegistrar;
use ZeroToProd\Framework\Blade\Registrars\ProductionRegistrar;
use ZeroToProd\Framework\Blade\Registrars\ViteRegistrar;
use ZeroToProd\Framework\Config;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::blade_directives,
    addCase: 'Add enum case with #[ResolvesTo] attribute pointing to a class carrying #[BladeRegistrar].'
)]
enum BladeDirective: string
{
    #[ResolvesTo(ProductionRegistrar::class)]
    case production = 'production';

    #[ResolvesTo(EnvRegistrar::class)]
    case env = 'env';

    #[ResolvesTo(ViteRegistrar::class)]
    case vite = 'vite';

    #[ResolvesTo(HtmxRegistrar::class)]
    case htmx = 'htmx';

    #[ResolvesTo(HotReloadRegistrar::class)]
    case hotReload = 'hotReload';

    public function register(Blade $Blade, Config $Config, Vite $Vite): void
    {
        $registrar = new ReflectionEnumBackedCase(self::class, $this->name)
            ->getAttributes(ResolvesTo::class)[0]
            ->newInstance()
            ->newResolver();

        assert(method_exists(object_or_class: $registrar, method: 'register'));

        $registrar->register($this->value, $Blade, $Config, $Vite);
    }
}
