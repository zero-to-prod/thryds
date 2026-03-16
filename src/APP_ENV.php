<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

enum APP_ENV: string
{
    case production = 'production';
    case development = 'development';
}
