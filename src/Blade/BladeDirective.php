<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ReflectionEnumBackedCase;
use Tempest\Blade\Blade;
use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\Attributes\ResolvesTo;
use ZeroToProd\Thryds\Blade\Registrars\EnvRegistrar;
use ZeroToProd\Thryds\Blade\Registrars\HotReloadRegistrar;
use ZeroToProd\Thryds\Blade\Registrars\HtmxRegistrar;
use ZeroToProd\Thryds\Blade\Registrars\ProductionRegistrar;
use ZeroToProd\Thryds\Blade\Registrars\ViteRegistrar;
use ZeroToProd\Thryds\Config;
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
