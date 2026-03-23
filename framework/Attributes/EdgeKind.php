<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Attributes;

use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::edge_kinds,
    addCase: 'Add enum case. Then use it in an #[Edge] attribute on an attribute class.'
)]
enum EdgeKind: string
{
    case type_system = 'type-system';
    case data_flow = 'data-flow';
    case composition = 'composition';
    case navigation = 'navigation';
    case schema = 'schema';
}
