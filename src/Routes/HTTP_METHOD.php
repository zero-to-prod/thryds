<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Helpers\ClosedSet;

#[ClosedSet(domain: 'HTTP methods', used_in: [[WebRoutes::class, 'register']])]
enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
