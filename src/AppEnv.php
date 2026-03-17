<?php

declare(strict_types=1);

namespace ZeroToProd\Thryds;

use ZeroToProd\Thryds\Helpers\LimitsChoices;

#[LimitsChoices]
enum AppEnv: string
{
    case production = 'production';
    case development = 'development';
}
