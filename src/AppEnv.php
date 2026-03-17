<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
