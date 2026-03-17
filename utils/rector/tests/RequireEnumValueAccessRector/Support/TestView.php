<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireEnumValueAccessRector;

enum TestView: string
{
    case home = 'home';
    case error = 'error';
}
