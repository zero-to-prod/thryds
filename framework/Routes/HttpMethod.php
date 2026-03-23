<?php

declare(strict_types=1);

namespace ZeroToProd\Framework\Routes;

use ZeroToProd\Framework\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(
    Domain::http_methods,
    addCase: 'Add enum case. No other changes needed — RouteRegistrar::register() accepts any HttpMethod.'
)]
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
