<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireViewEnumInMakeCallRector;

enum TestView: string
{
    case home = 'home';
    case error = 'error';
    case about = 'about';
}
