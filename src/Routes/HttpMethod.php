<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Attributes\ClosedSet;
use ZeroToProd\Thryds\UI\Domain;

#[ClosedSet(Domain::http_methods, addCase: 'Add enum case. No other changes needed — WebRoutes::register() accepts any HTTP_METHOD.')]
enum HttpMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
