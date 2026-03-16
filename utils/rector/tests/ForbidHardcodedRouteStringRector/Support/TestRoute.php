<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\ForbidHardcodedRouteStringRector;

enum TestRoute: string
{
    case home = '/';
    case about = '/about';
}
