<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Blade;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(Domain::blade_directives, addCase: '1. Add enum case. 2. Register directive in BladeDirectives::register().')]
enum BladeDirective: string
{
    case production = 'production';
    case env = 'env';
    case vite = 'vite';
    case htmx = 'htmx';
    case hotReload = 'hotReload';
}
