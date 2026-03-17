<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds\Routes;

use ZeroToProd\Thryds\Helpers\LimitsChoices;

#[LimitsChoices]
enum HTTP_METHOD: string
{
    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
}
