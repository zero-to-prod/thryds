<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Helpers;

#[ClosedSet(Domain::blade_directives, addCase: '1. Add enum case. 2. Register directive in BladeDirectives::register().')]
enum BladeDirective: string
{
    case production = 'production';
    case env = 'env';
    case vite = 'vite';
    case htmx = 'htmx';
    case hotReload = 'hotReload';
}
