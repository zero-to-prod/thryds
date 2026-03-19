<?php

declare(strict_types=1);

namespace Utils\Rector\Tests\RequireViewModelDataInMakeCallRector\Support;

enum TestView: string
{
    case home = 'home';
    case register = 'register';
}
