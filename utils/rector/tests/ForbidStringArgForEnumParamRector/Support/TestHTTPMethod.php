<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ForbidStringArgForEnumParamRector;

enum TestHTTPMethod: string
{
    case GET = 'GET';
    case POST = 'POST';
}
