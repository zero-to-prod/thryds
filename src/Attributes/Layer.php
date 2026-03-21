<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Attributes;

use ZeroToProd\Thryds\UI\Domain;

/**
 * Closed set of namespace-to-layer mappings for the attribute graph.
 *
 * Each case's backing value is the layer name in graph output.
 * The namespace segment defaults to PascalCase of the layer name,
 * overridden via #[Segment] where it differs.
 */
#[ClosedSet(
    Domain::namespace_layers,
    addCase: <<<TEXT
        1. Add enum case where the value is the layer name.
        2. Add #[Segment] if the namespace segment differs from the PascalCase of the case name.
    TEXT
)]
enum Layer: string
{
    case attributes = 'attributes';

    #[Segment('Blade')]
    case views = 'views';

    case controllers = 'controllers';
    case requests = 'requests';

    #[Segment('Routes')]
    case routing = 'routing';

    case schema = 'schema';
    case tables = 'tables';

    #[Segment('UI')]
    case ui = 'ui';

    case validation = 'validation';

    #[Segment('ViewModels')]
    case viewmodels = 'viewmodels';
}
