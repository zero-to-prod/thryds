<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
